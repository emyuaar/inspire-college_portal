@extends('layouts.partner')

@section('title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto">
    <form method="POST" action="{{ route('partner.profile.update') }}" class="space-y-6">
        @csrf

        {{-- Section 1: Basic Information --}}
        <x-ui.card title="Profile Details" subtitle="Update your basic account information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input 
                    label="First Name" 
                    name="first_name" 
                    value="{{ old('first_name', $user->first_name) }}" 
                    required 
                />
                <x-ui.input 
                    label="Middle Name" 
                    name="middle_name" 
                    value="{{ old('middle_name', $user->middle_name) }}" 
                />
                <x-ui.input 
                    label="Surname" 
                    name="sur_name" 
                    value="{{ old('sur_name', $user->sur_name) }}" 
                    required 
                />
                <x-ui.input 
                    label="Email Address" 
                    value="{{ $user->email_address }}" 
                    disabled 
                />
                <p class="text-[11px] text-slate-500 md:col-span-2 -mt-2">
                    Email address cannot be changed. Please contact support if you need to update your email.
                </p>
            </div>
        </x-ui.card>

        {{-- Section 2: Contact Details --}}
        <x-ui.card title="Contact Information" subtitle="How we can reach you">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input 
                    label="Contact Number" 
                    name="contact" 
                    value="{{ old('contact', $detail->contact ?? '') }}" 
                />

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Gender</label>
                    <select name="gender" class="block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 focus:bg-white focus:border-ds-pink focus:ring-ds-pink transition-all text-sm shadow-sm">
                        <option value="">Select Gender</option>
                        <option value="male" @selected(old('gender', $detail->gender ?? '') === 'male')>Male</option>
                        <option value="female" @selected(old('gender', $detail->gender ?? '') === 'female')>Female</option>
                        <option value="other" @selected(old('gender', $detail->gender ?? '') === 'other')>Other</option>
                    </select>
                </div>
            </div>
        </x-ui.card>

        {{-- Section 3: Security --}}
        <x-ui.card title="Change Password" subtitle="Update your password to keep your account secure">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <x-ui.input 
                        type="password" 
                        label="Current Password" 
                        name="current_password" 
                        placeholder="Required only if changing password"
                    />
                </div>
                <x-ui.input 
                    type="password" 
                    label="New Password" 
                    name="new_password" 
                    placeholder="Min 8 characters"
                />
                <x-ui.input 
                    type="password" 
                    label="Confirm New Password" 
                    name="new_password_confirmation" 
                />
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.button type="submit" variant="primary" class="px-8 shadow-lg shadow-blue-900/10">
                Update Profile
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
