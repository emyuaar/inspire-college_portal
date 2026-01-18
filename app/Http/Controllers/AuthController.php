<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login page (simple form).
     */
    public function showLoginForm()
    {
        // e.g. resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * Handle login POST.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // humara column email_address hai
        $user = User::where('email_address', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput($request->only('email'));
        }

        // Optional: status check (only active users)
        // if ($user->status_id != SOME_ACTIVE_ID) { ... }

        Auth::login($user, $request->boolean('remember'));

        // Redirect based on org_id logic
        if ($user->isOrganization()) {
            // org account (id == org_id)
            return redirect()->route('partner.learners.index');
        }

        if ($user->isStandaloneLearner()) {
            // learner with no org (org_id = 0)
            return redirect()->route('portal.learner.dashboard');
        }

        if ($user->isOrgLearner()) {
            // learner linked to organization (org_id > 0 && org_id != id)
            return redirect()->route('portal.learner.dashboard');
        }

        // fallback – just in case
        return redirect()->route('portal.dashboard');
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
