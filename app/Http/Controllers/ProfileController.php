<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\Crm\UserDetail;
use App\Models\Crm\LearnerDocument;
use App\Models\Crm\LearnerOnboardingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function editPersonal()
    {
        $user = Auth::user(); // from inspirecollege_portal.users

        // Additional details from CRM
        $detail = UserDetail::where('learner_id', $user->id)->first();

        // Documents grouped by category
        $identityDocs   = LearnerDocument::where('learner_id', $user->id)->where('category', 'identity')->get();
        $educationDocs  = LearnerDocument::where('learner_id', $user->id)->where('category', 'education')->get();
        $experienceDocs = LearnerDocument::where('learner_id', $user->id)->where('category', 'experience')->get();

        return view('profile.personal', compact(
            'user',
            'detail',
            'identityDocs',
            'educationDocs',
            'experienceDocs'
        ));
    }

    public function updatePersonal(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'contact'        => ['nullable', 'string', 'max:30'],
            'personal_email' => ['required', 'email', 'max:100'], // Required as per issue desc
            'dob'            => ['nullable', 'date'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'country'        => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'zip_code'       => ['nullable', 'string', 'max:20'],

            'identity_documents.*'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'education_documents.*'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'experience_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $detail = UserDetail::firstOrNew(['learner_id' => $user->id]);
        $detail->fill($request->only([
            'contact',
            'personal_email',
            'dob',
            'gender',
            'address_line_1',
            'address_line_2',
            'country',
            'city',
            'state',
            'zip_code',
        ]));
        $detail->save();

        $this->storeDocuments($request, $user->id, 'identity', 'identity_documents');
        $this->storeDocuments($request, $user->id, 'education', 'education_documents');
        $this->storeDocuments($request, $user->id, 'experience', 'experience_documents');

        // mark completed + reset verified (admin will re-verify)
        LearnerOnboardingStatus::updateOrCreate(
            ['learner_id' => $user->id],
            [
                'personal_info_completed' => 1,
                'personal_verified'       => 0,
                'personal_completed_at'   => now(),
            ]
        );

        // if previously denied, move to pending
        DB::connection('mysql_crm')
            ->table('enrolments')
            ->where('learner_id', $user->id)
            ->where('status_id', 3)
            ->update(['status_id' => 1]);

        return redirect()
            ->route('portal.learner.dashboard')
            ->with('success', 'Personal information saved successfully.');
    }

    // ========== RPL INFORMATION ==========

    public function editRpl()
    {
        $user = Auth::user();

        $onboarding = LearnerOnboardingStatus::where('learner_id', $user->id)->first();

        return view('profile.rpl', [
            'user'       => $user,
            'onboarding' => $onboarding,
        ]);
    }

    public function updateRpl(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'confirm_no_rpl' => ['accepted'],
        ]);

        LearnerOnboardingStatus::updateOrCreate(
            ['learner_id' => $user->id],
            [
                'rpl_info_completed' => 1,
                'rpl_verified'       => 0,
            ]
        );

        DB::connection('mysql_crm')
            ->table('enrolments')
            ->where('learner_id', $user->id)
            ->where('status_id', 3)
            ->update(['status_id' => 1]);

        return redirect()
            ->route('portal.learner.dashboard')
            ->with('success', 'RPL information saved.');
    }

    // ========== DISABILITY INFORMATION ==========

    public function editDisability()
    {
        $user = Auth::user();

        $onboarding = LearnerOnboardingStatus::where('learner_id', $user->id)->first();

        return view('profile.disability', [
            'user'       => $user,
            'onboarding' => $onboarding,
        ]);
    }

    public function updateDisability(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'confirm_no_disability' => ['accepted'],
        ]);

        LearnerOnboardingStatus::updateOrCreate(
            ['learner_id' => $user->id],
            [
                'disability_info_completed' => 1,
                'disability_verified'       => 0,
            ]
        );

        DB::connection('mysql_crm')
            ->table('enrolments')
            ->where('learner_id', $user->id)
            ->where('status_id', 3)
            ->update(['status_id' => 1]);

        return redirect()
            ->route('portal.learner.dashboard')
            ->with('success', 'Disability information saved.');
    }

    private function storeDocuments(Request $request, int $learnerId, string $category, string $inputName): void
    {
        // multiple upload: identity_documents[] etc
        if (!$request->hasFile($inputName)) {
            return;
        }

        $files = $request->file($inputName);

        // sometimes Laravel returns single file instead of array
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            // storage path
            $dir = "learner_documents/{$learnerId}/{$category}";

            // clean unique filename
            $ext = strtolower($file->getClientOriginalExtension());
            $safeName = Str::uuid()->toString() . '.' . $ext;

            // store in public disk (storage/app/public/...)
            $path = $file->storeAs($dir, $safeName, 'public');

            // DB save (update model/table fields as per your schema)
            \App\Models\Crm\LearnerDocument::create([
                'learner_id' => $learnerId,
                'category'   => $category,       // identity / education / experience
                'title'      => $file->getClientOriginalName(),
                'file_path'  => $path,           // e.g. learner_documents/12/identity/uuid.pdf
                'mime'       => $file->getClientMimeType(),
                'size'       => $file->getSize(),
            ]);
        }
    }

    // ACCOUNT SETTINGS (simple profile + password)
    // ============================================
    public function editAccount()
    {
        $user = Auth::user(); // inspirecollege_portal.users

        // Extra details from CRM (user_detail)
        $detail = UserDetail::firstOrNew(['learner_id' => $user->id]);

        return view('profile.account', [
            'user'   => $user,
            'detail' => $detail,
        ]);
    }

    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        // ---------- VALIDATION ----------
        $validated = $request->validate([
            // portal.users fields
            'first_name'   => ['required', 'string', 'max:100'],
            'middle_name'  => ['nullable', 'string', 'max:100'],
            'sur_name'     => ['required', 'string', 'max:100'],

            // CRM.user_detail fields
            'contact'        => ['nullable', 'string', 'max:30'],
            'dob'            => ['nullable', 'date'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'country'        => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'zip_code'       => ['nullable', 'string', 'max:20'],

            // Password (optional)
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password'     => ['nullable', 'confirmed', 'min:8'],
        ]);

        // ---------- UPDATE PORTAL USER ----------
        $user->first_name    = $validated['first_name'];
        $user->middle_name   = $validated['middle_name'] ?? null;
        $user->sur_name      = $validated['sur_name'];

        // if new password provided, update it
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        // ---------- UPDATE CRM USER_DETAIL ----------
        $detail = UserDetail::firstOrNew(['learner_id' => $user->id]);
        $detail->fill([
            'contact'        => $validated['contact']        ?? null,
            'dob'            => $validated['dob']            ?? null,
            'gender'         => $validated['gender']         ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'country'        => $validated['country']        ?? null,
            'city'           => $validated['city']           ?? null,
            'state'          => $validated['state']          ?? null,
            'zip_code'       => $validated['zip_code']       ?? null,
        ]);
        $detail->save();

        return redirect()
            ->route('portal.settings.profile')
            ->with('success', 'Your account details have been updated successfully.');
    }
}
