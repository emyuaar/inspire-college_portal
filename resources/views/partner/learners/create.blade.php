@extends('layouts.partner')

@section('title', 'Add New Learner')
@section('active-page', 'create_learner')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('partner.learners.index') }}" class="group flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-ds-navy hover:border-ds-navy transition-all shadow-sm">
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Step 1: Create Learner Profile</h1>
            <p class="text-sm text-slate-500">Enter the learner details. Login credentials are sent after payment approval.</p>
        </div>
    </div>

    <form action="{{ route('partner.learners.store') }}" method="POST" class="space-y-6">
        @csrf

        <x-ui.card padding="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                <h2 class="text-lg font-bold text-slate-900">Personal Information</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" required value="{{ old('first_name') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                    @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                    @error('middle_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="sur_name" required value="{{ old('sur_name') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                    @error('sur_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Personal Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email_address" required value="{{ old('email_address') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm" placeholder="learner@example.com">
                    <p class="mt-1 text-[11px] text-slate-500">For communication. Login email will be created after activation.</p>
                    @error('email_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                    @error('contact_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                    @error('dob') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Gender</label>
                    <select name="gender" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <hr class="my-6 border-slate-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Address Line 1</label>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Address Line 2</label>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">State / County</label>
                    <input type="text" name="state" value="{{ old('state') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Country</label>
                    <input type="text" name="country" value="{{ old('country') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Postcode / Zip</label>
                    <input type="text" name="zip_code" value="{{ old('zip_code') }}" class="w-full rounded-lg border-slate-300 focus:ring-ds-navy text-sm">
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-bold text-blue-900 mb-1">No Password Required</h3>
                    <p class="text-[13px] text-blue-700 leading-relaxed">
                        After the first payment is approved, the learner will receive a secure email to set their own password.
                    </p>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('partner.learners.index') }}" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</a>
            <button type="submit" class="px-8 py-2.5 rounded-full bg-ds-navy text-white font-bold text-sm hover:bg-ds-navy/90 transition-all shadow-md">
                Create Profile & Continue
            </button>
        </div>
    </form>
</div>
@endsection
