<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountSetupTokenService;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LearnerPasswordSetupController extends Controller
{
    private const SETUP_INVALID_MESSAGE = 'This account setup link is no longer valid. Please request a new setup email.';
    private const SETUP_USED_MESSAGE = 'Your account has already been set up. Please sign in.';

    public function show(Request $request, string $token, AccountSetupTokenService $setupTokens)
    {
        $email = $request->query('email');
        $setup = $setupTokens->inspect($token, $email);

        if ($setup['status'] === AccountSetupTokenService::STATUS_ALREADY_SETUP) {
            return redirect()->route('portal.login')->with('info', self::SETUP_USED_MESSAGE);
        }

        if ($setup['status'] === AccountSetupTokenService::STATUS_VALID) {
            return $this->passwordView($setup['user'], $token, 'account_setup');
        }

        if ($request->routeIs('learner.activate')) {
            return redirect()->route('portal.login')->with('error', self::SETUP_INVALID_MESSAGE);
        }

        $reset = $this->inspectPasswordResetToken($token, $email);

        if ($reset['status'] !== 'valid') {
            return redirect()->route('portal.login')
                ->with('error', $reset['status'] === 'expired'
                    ? 'This password reset link has expired.'
                    : 'Invalid or expired password reset link.');
        }

        return $this->passwordView($reset['user'], $token, 'password_reset');
    }

    public function store(Request $request, MicrosoftGraphService $graphService, AccountSetupTokenService $setupTokens)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'flow' => ['nullable', 'in:account_setup,password_reset'],
            'password' => $this->passwordRules(),
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        $flow = $request->input('flow', 'account_setup');

        if ($flow === 'account_setup') {
            return $this->storeAccountSetupPassword($request, $setupTokens, $graphService);
        }

        return $this->storePasswordReset($request, $graphService);
    }

    private function storeAccountSetupPassword(Request $request, AccountSetupTokenService $setupTokens, MicrosoftGraphService $graphService)
    {
        $setup = $setupTokens->inspect($request->token, $request->input('email'));

        if ($setup['status'] === AccountSetupTokenService::STATUS_ALREADY_SETUP) {
            return redirect()->route('portal.login')->with('info', self::SETUP_USED_MESSAGE);
        }

        if ($setup['status'] !== AccountSetupTokenService::STATUS_VALID) {
            return back()->withErrors(['email' => self::SETUP_INVALID_MESSAGE])->withInput($request->except('password', 'password_confirmation'));
        }

        $passwordError = $this->personalizedPasswordError($setup['user'], $request->password);
        if ($passwordError) {
            return back()->withErrors(['password' => $passwordError])->withInput($request->except('password', 'password_confirmation'));
        }

        $result = $setupTokens->consume($request->token, $request->password, $request->input('email'));

        if ($result['status'] === AccountSetupTokenService::STATUS_ALREADY_SETUP) {
            return redirect()->route('portal.login')->with('info', self::SETUP_USED_MESSAGE);
        }

        if ($result['status'] !== AccountSetupTokenService::STATUS_VALID) {
            return back()->withErrors(['email' => self::SETUP_INVALID_MESSAGE])->withInput($request->except('password', 'password_confirmation'));
        }

        $user = $result['user'];
        $this->syncCrmAfterPasswordSetup($user);
        $this->syncMicrosoftPassword($user, $request->password, $graphService);

        Log::info('Partner learner account setup completed', [
            'portal_user_id' => $user->id,
        ]);

        return redirect()->route('portal.login')
            ->with('success', 'Your password has been set successfully. You can now log in.');
    }

    private function storePasswordReset(Request $request, MicrosoftGraphService $graphService)
    {
        $reset = $this->inspectPasswordResetToken($request->token, $request->input('email'));

        if ($reset['status'] !== 'valid') {
            return back()->withErrors([
                'email' => $reset['status'] === 'expired'
                    ? 'This password reset link has expired.'
                    : 'Invalid or expired password reset link.',
            ]);
        }

        $passwordError = $this->personalizedPasswordError($reset['user'], $request->password);
        if ($passwordError) {
            return back()->withErrors(['password' => $passwordError])->withInput($request->except('password', 'password_confirmation'));
        }

        DB::connection('mysql_portal')->transaction(function () use ($request, $reset) {
            $user = User::query()->whereKey($reset['user']->id)->lockForUpdate()->firstOrFail();
            $user->password = Hash::make($request->password);
            if (empty($user->password_set_at)) {
                $user->password_set_at = now();
            }
            $user->save();

            DB::connection('mysql_portal')->table('password_reset_tokens')
                ->where('email', $user->email_address)
                ->delete();
        });

        $user = User::query()->findOrFail($reset['user']->id);
        $this->syncMicrosoftPassword($user, $request->password, $graphService);

        return redirect()->route('portal.login')
            ->with('success', 'Your password has been reset successfully. You can now log in.');
    }

    private function passwordView(User $user, string $token, string $flow)
    {
        Log::info('Learner password page loaded', [
            'token_present' => true,
            'portal_user_id' => $user->id,
            'flow' => $flow,
        ]);

        return view('auth.reset-password', [
            'learner' => null,
            'accountEmail' => $user->email_address,
            'token' => $token,
            'flow' => $flow,
            'is_activation' => $flow === 'account_setup',
        ]);
    }

    private function inspectPasswordResetToken(string $token, ?string $email): array
    {
        if (! $email) {
            return ['status' => 'invalid'];
        }

        $record = DB::connection('mysql_portal')->table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record || (! Hash::check($token, $record->token) && ! hash_equals((string) $record->token, $token))) {
            return ['status' => 'invalid'];
        }

        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);
        if (Carbon::parse($record->created_at)->addMinutes($expiresInMinutes)->isPast()) {
            return ['status' => 'expired'];
        }

        $user = User::query()->where('email_address', $email)->first();
        if (! $user) {
            return ['status' => 'invalid'];
        }

        return ['status' => 'valid', 'record' => $record, 'user' => $user];
    }

    private function passwordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'max:256',
            'confirmed',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
            function ($attribute, $value, $fail) {
                $blockedWords = [
                    'password123!',
                    'directskills123!',
                    'welcome123!',
                    'qwerty123!',
                    'admin123!',
                ];

                if (in_array(strtolower($value), array_map('strtolower', $blockedWords), true)) {
                    $fail('This password is too common. Please choose a stronger password.');
                }
            },
        ];
    }

    private function personalizedPasswordError(User $user, string $password): ?string
    {
        $lowerPassword = strtolower($password);

        if (! empty($user->first_name) && str_contains($lowerPassword, strtolower($user->first_name))) {
            return 'Password must not contain your first name.';
        }

        if (! empty($user->sur_name) && str_contains($lowerPassword, strtolower($user->sur_name))) {
            return 'Password must not contain your last name.';
        }

        if (! empty($user->email_address)) {
            $emailPrefix = strtolower(strtok($user->email_address, '@'));
            if ($emailPrefix && str_contains($lowerPassword, $emailPrefix)) {
                return 'Password must not contain your email username.';
            }
        }

        return null;
    }

    private function syncCrmAfterPasswordSetup(User $user): void
    {
        try {
            $partnerLearner = \App\Models\Crm\PartnerLearner::where('user_id', $user->id)->first();
            if (! $partnerLearner) {
                return;
            }

            $partnerLearner->update([
                'activation_status' => 'completed',
                'account_status' => 'active',
            ]);

            $userDetail = DB::connection('mysql_crm')->table('user_detail')->where('learner_id', $user->id)->first();
            $detailData = [
                'personal_email' => $userDetail->personal_email ?? ($partnerLearner->personal_email ?? $partnerLearner->email),
                'contact' => $userDetail->contact ?? $partnerLearner->phone,
                'dob' => $userDetail->dob ?? $partnerLearner->dob,
                'address_line_1' => $userDetail->address_line_1 ?? $partnerLearner->address,
                'country' => $userDetail->country ?? $partnerLearner->country,
                'updated_at' => now(),
            ];

            if ($userDetail) {
                DB::connection('mysql_crm')->table('user_detail')->where('learner_id', $user->id)->update($detailData);
            } else {
                $detailData['learner_id'] = $user->id;
                $detailData['created_at'] = now();
                DB::connection('mysql_crm')->table('user_detail')->insert($detailData);
            }
        } catch (\Throwable $e) {
            Log::error('CRM sync failed after account setup password save.', [
                'portal_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncMicrosoftPassword(User $user, string $password, MicrosoftGraphService $graphService): void
    {
        try {
            if ($user->ms_user_id) {
                $graphService->updateUserPassword($user->ms_user_id, $password);
            } else {
                $graphService->provisionLearner($user, $password);
            }
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph password sync failed.', [
                'portal_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $user->update(['ms_error_message' => 'Password Sync/Provision Failed: ' . $e->getMessage()]);
        }
    }
}
