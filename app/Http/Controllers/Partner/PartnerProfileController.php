<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Crm\UserDetail;

class PartnerProfileController extends Controller
{
    /**
     * Show the partner profile edit form.
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Partners might not have a record in UserDetail yet, so we use firstOrNew
        $detail = UserDetail::firstOrNew(['learner_id' => $user->id]);

        return view('partner.profile', [
            'user'   => $user,
            'detail' => $detail,
        ]);
    }

    /**
     * Update the partner profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'middle_name'  => ['nullable', 'string', 'max:100'],
            'sur_name'     => ['required', 'string', 'max:100'],
            'contact'      => ['nullable', 'string', 'max:30'],
            'gender'       => ['nullable', 'in:male,female,other'],
            
            // Password fields
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password'     => ['nullable', 'confirmed', 'min:8'],
        ]);

        // Update User model (portal.users)
        $user->first_name  = $validated['first_name'];
        $user->middle_name = $validated['middle_name'] ?? null;
        $user->sur_name    = $validated['sur_name'];

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }
        $user->save();

        // Update UserDetail (CRM user_detail)
        $detail = UserDetail::firstOrNew(['learner_id' => $user->id]);
        $detail->fill([
            'contact' => $validated['contact'] ?? null,
            'gender'  => $validated['gender']  ?? null,
        ]);
        $detail->save();

        return redirect()
            ->route('partner.profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
