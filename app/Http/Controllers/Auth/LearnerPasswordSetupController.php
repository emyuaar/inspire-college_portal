<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class LearnerPasswordSetupController extends Controller
{
    /**
     * Show the password setup form.
     */
    public function show(Request $request, $token)
    {
        // Try finding in learner_activations (New Secure Workflow)
        $activation = DB::table('learner_activations')->where('token', $token)->first();

        if ($activation) {
            // Enforce 1-hour expiration for activation links
            if (Carbon::parse($activation->created_at)->addHours(1)->isPast()) {
                DB::table('learner_activations')->where('token', $token)->delete();
                return redirect()->route('portal.login')
                    ->with('error', 'This activation link has expired. Please contact support.');
            }

            return view('auth.reset-password', [
                'token' => $token,
                'email' => $activation->email,
                'is_activation' => true
            ]);
        }

        // Fallback for standard password reset (existing logic)
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Store the new password.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $isActivation = false;
        $record = null;

        // 1. Check learner_activations first
        $record = DB::table('learner_activations')
            ->where('token', $request->token)
            ->where('email', $request->email)
            ->first();

        if ($record) {
            $isActivation = true;
            // Expiry check
            if (Carbon::parse($record->created_at)->addHours(1)->isPast()) {
                DB::table('learner_activations')->where('token', $request->token)->delete();
                return back()->withErrors(['email' => 'This activation link has expired.']);
            }
        } else {
            // 2. Fallback to standard password_reset_tokens
            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (! $record || ! Hash::check($request->token, $record->token)) {
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

        $user = User::where('email_address', $request->email)->firstOrFail();

        $user->password = Hash::make($request->password);
        $user->save();

        // Update PartnerLearner record if it exists
        $partnerLearner = \App\Models\Crm\PartnerLearner::where('user_id', $user->id)->first();
        if ($partnerLearner) {
            $partnerLearner->update([
                'activation_status' => 'completed',
                'account_status' => 'active'
            ]);
        }

        // Cleanup the token
        if ($isActivation) {
            DB::table('learner_activations')
                ->where('token', $request->token)
                ->delete();
        } else {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
        }

        return redirect()->route('portal.login')
            ->with('success', 'Your password has been set successfully. You can now log in.');
    }
}
