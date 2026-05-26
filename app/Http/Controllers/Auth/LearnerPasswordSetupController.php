<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\MicrosoftGraphService;

class LearnerPasswordSetupController extends Controller
{
    /**
     * Show the password setup form.
     */
    public function show(Request $request, $token)
    {
        $email = null;
        $isActivation = false;

        // 1. Check learner_activations first (New Secure Workflow)
        $activation = DB::table('learner_activations')->where('token', $token)->first();

        if ($activation) {
            $isActivation = true;
            $email = $activation->email;

            // Enforce 1-hour expiration for activation links
            if (Carbon::parse($activation->created_at)->addHours(1)->isPast()) {
                DB::table('learner_activations')->where('token', $token)->delete();
                return redirect()->route('portal.login')
                    ->with('error', 'This activation link has expired. Please contact support.');
            }
        } else {
            // 2. Fallback to standard password_reset_tokens
            $tokens = DB::table('password_reset_tokens')->get();
            foreach ($tokens as $t) {
                if (Hash::check($token, $t->token) || $token === $t->token) {
                    $email = $t->email;

                    // Enforce 24-hour expiration for standard reset links
                    if (Carbon::parse($t->created_at)->addHours(24)->isPast()) {
                        return redirect()->route('portal.login')
                            ->with('error', 'This password reset link has expired.');
                    }
                    break;
                }
            }
        }

        // Final fallback if email is not resolved from token
        if (!$email) {
            $email = $request->query('email');
        }

        // Find user by email to resolve learner
        $user = null;
        $learner = null;
        if ($email) {
            $user = User::where('email_address', $email)->first();
            if ($user) {
                $learner = \App\Models\Crm\PartnerLearner::where('user_id', $user->id)->first();
            }
        }

        $accountEmail = null;
        if ($learner) {
            $accountEmail = $learner->generated_email 
                ?? $learner->ds_email 
                ?? $learner->portal_email 
                ?? $learner->email 
                ?? null;
        }

        if (!$accountEmail) {
            $accountEmail = ($user ? $user->email_address : null) ?? $email;
        }

        Log::info('Set password page loaded', [
            'token_present' => !empty($token),
            'learner_id' => $learner->id ?? null,
            'account_email' => $accountEmail ?? null,
        ]);

        return view('auth.reset-password', [
            'learner' => $learner,
            'accountEmail' => $accountEmail,
            'token' => $token,
            'is_activation' => $isActivation
        ]);
    }

    /**
     * Store the new password.
     */
    public function store(Request $request, MicrosoftGraphService $graphService)
    {
        Log::info('Set password form submitted', [
            'token_present' => $request->filled('token'),
            'email_present' => $request->filled('email'),
        ]);

        $request->validate([
            'token' => ['required'],
            'password' => [
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
                    $lower = strtolower($value);

                    $blockedWords = [
                        'password123!',
                        'directskills123!',
                        'welcome123!',
                        'qwerty123!',
                        'admin123!',
                    ];

                    if (in_array($lower, array_map('strtolower', $blockedWords))) {
                        $fail('This password is too common. Please choose a stronger password.');
                    }
                },
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);

        $isActivation = false;
        $record = null;
        $email = null;

        // 1. Check learner_activations first
        $record = DB::table('learner_activations')
            ->where('token', $request->token)
            ->first();

        if ($record) {
            $isActivation = true;
            $email = $record->email;
            // Expiry check
            if (Carbon::parse($record->created_at)->addHours(1)->isPast()) {
                DB::table('learner_activations')->where('token', $request->token)->delete();
                return back()->withErrors(['email' => 'This activation link has expired.']);
            }
        } else {
            // 2. Fallback to standard password_reset_tokens
            // Laravel's default tokens are hashed in the DB, but our token might be plain text.
            // Actually, we must loop through tokens if they are hashed, or just assume the plain token if not.
            // Assuming we check the unhashed token in DB or hashed token.
            $tokens = DB::table('password_reset_tokens')->get();
            foreach ($tokens as $t) {
                if (Hash::check($request->token, $t->token) || $request->token === $t->token) {
                    $record = $t;
                    $email = $t->email;
                    break;
                }
            }

            if (! $record) {
                return back()->withErrors([
                    'email' => 'Invalid or expired password reset link.',
                ]);
            }

            if (Carbon::parse($record->created_at)->addHours(24)->isPast()) {
                return back()->withErrors([
                    'email' => 'This password reset link has expired.',
                ]);
            }
        }

        $user = User::where('email_address', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        // Additional password validation using user details
        $lowerPassword = strtolower($request->password);
        if (!empty($user->first_name) && str_contains($lowerPassword, strtolower($user->first_name))) {
            return back()->withErrors(['password' => 'Password must not contain your first name.']);
        }

        if (!empty($user->sur_name) && str_contains($lowerPassword, strtolower($user->sur_name))) {
            return back()->withErrors(['password' => 'Password must not contain your last name.']);
        }

        if (!empty($user->email_address)) {
            $emailPrefix = strtolower(strtok($user->email_address, '@'));
            if ($emailPrefix && str_contains($lowerPassword, $emailPrefix)) {
                return back()->withErrors(['password' => 'Password must not contain your email username.']);
            }
        }

        Log::info('Learner password setup started', [
            'portal_user_id' => $user->id,
            'email' => $user->email_address,
            'is_activation' => $isActivation
        ]);

        DB::connection('mysql_crm')->beginTransaction();
        DB::connection('mysql_portal')->beginTransaction();

        try {
            $user->password = Hash::make($request->password);
            $user->password_set_at = now();
            if ($user->status_id == 1) { // If pending
                $user->status_id = 2; // Active
            }
            $user->save();

            // Update PartnerLearner and CRM user_details if it exists
            $partnerLearner = \App\Models\Crm\PartnerLearner::where('user_id', $user->id)->first();
            if ($partnerLearner) {
                $partnerLearner->update([
                    'activation_status' => 'completed',
                    'account_status' => 'active'
                ]);

                // Sync data to CRM user_details safely
                $userDetail = DB::connection('mysql_crm')->table('user_detail')->where('learner_id', $user->id)->first();
                $updateData = [];

                if (!$userDetail) {
                    $updateData = [
                        'learner_id' => $user->id,
                        'personal_email' => $partnerLearner->personal_email ?? $partnerLearner->email,
                        'contact' => $partnerLearner->phone,
                        'dob' => $partnerLearner->dob,
                        'address_line_1' => $partnerLearner->address,
                        'country' => $partnerLearner->country,
                    ];
                    DB::connection('mysql_crm')->table('user_detail')->insert($updateData);
                } else {
                    $updateData = [
                        'personal_email' => $userDetail->personal_email ?? ($partnerLearner->personal_email ?? $partnerLearner->email),
                        'contact' => $userDetail->contact ?? $partnerLearner->phone,
                        'dob' => $userDetail->dob ?? $partnerLearner->dob,
                        'address_line_1' => $userDetail->address_line_1 ?? $partnerLearner->address,
                        'country' => $userDetail->country ?? $partnerLearner->country,
                    ];
                    DB::connection('mysql_crm')->table('user_detail')->where('learner_id', $user->id)->update($updateData);
                }
                
                Log::info('CRM user_detail updated during password setup', [
                    'learner_id' => $user->id,
                ]);
            }

            // Sync with Microsoft Graph
            try {
                if ($user->ms_user_id) {
                    $graphService->updateUserPassword($user->ms_user_id, $request->password);
                    Log::info("MSGraphService: Synced password for MS user {$user->ms_user_id}");
                } else {
                    $graphService->provisionLearner($user, $request->password);
                    Log::info("MSGraphService: Provisioned MS user for {$user->email_address}");
                }
            } catch (\Exception $e) {
                Log::error("MSGraphService Error during password setup for user {$user->id}: " . $e->getMessage());
                // Update portal user ms_error_message without failing transaction
                $user->update(['ms_error_message' => 'Password Sync/Provision Failed: ' . $e->getMessage()]);
            }

            // Cleanup the token
            if ($isActivation) {
                DB::table('learner_activations')
                    ->where('token', $request->token)
                    ->delete();
            } else {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();
            }

            DB::connection('mysql_crm')->commit();
            DB::connection('mysql_portal')->commit();

            Log::info('Partner learner password setup completed', [
                'portal_user_id' => $user->id,
            ]);

            return redirect()->route('portal.login')
                ->with('success', 'Your password has been set successfully. You can now log in.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            DB::connection('mysql_portal')->rollBack();
            
            Log::error('Learner password setup failed', [
                'portal_user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['email' => 'An error occurred while saving your details. Please try again or contact support.']);
        }
    }
}
