<x-app-layout page-title="Account Settings" active-page="profile">

    <form method="POST" action="{{ route('portal.settings.profile.update') }}" class="max-w-4xl mx-auto space-y-6">
        @csrf

        {{-- Section 1: Read Only Profile --}}
        <x-ui.card title="Profile Details" subtitle="Your personal information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="First Name" value="{{ $user->first_name }}" disabled />
                <x-ui.input label="Middle Name" value="{{ $user->middle_name }}" disabled />
                <x-ui.input label="Surname" value="{{ $user->sur_name }}" disabled />
                <x-ui.input label="Email Address" value="{{ $user->email_address }}" disabled />
            </div>
            {{-- Hidden inputs to pass validation if required, though usually better handled by backend ignoring disabled fields --}}
             <input type="hidden" name="first_name" value="{{ $user->first_name }}">
             <input type="hidden" name="middle_name" value="{{ $user->middle_name }}">
             <input type="hidden" name="sur_name" value="{{ $user->sur_name }}">
        </x-ui.card>

        {{-- Section 2: Contact & Address --}}
        <x-ui.card title="Contact & Address" subtitle="Update your contact details">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input 
                    label="Contact Number" 
                    name="contact" 
                    value="{{ old('contact', $detail->contact ?? '') }}" 
                />

                <x-ui.input 
                    type="date" 
                    label="Date of Birth" 
                    name="dob" 
                    value="{{ old('dob', optional($detail)->dob ? \Carbon\Carbon::parse($detail->dob)->format('Y-m-d') : '') }}" 
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

                <x-ui.input 
                    label="Country" 
                    name="country" 
                    value="{{ old('country', $detail->country ?? '') }}" 
                />

                <div class="md:col-span-2">
                    <x-ui.input 
                        label="Address Line 1" 
                        name="address_line_1" 
                        value="{{ old('address_line_1', $detail->address_line_1 ?? '') }}" 
                    />
                </div>

                <div class="md:col-span-2">
                    <x-ui.input 
                        label="Address Line 2" 
                        name="address_line_2" 
                        value="{{ old('address_line_2', $detail->address_line_2 ?? '') }}" 
                    />
                </div>

                <x-ui.input 
                    label="City" 
                    name="city" 
                    value="{{ old('city', $detail->city ?? '') }}" 
                />

                <x-ui.input 
                    label="State" 
                    name="state" 
                    value="{{ old('state', $detail->state ?? '') }}" 
                />

                <x-ui.input 
                    label="Postcode / ZIP" 
                    name="zip_code" 
                    value="{{ old('zip_code', $detail->zip_code ?? '') }}" 
                />
            </div>
        </x-ui.card>

        {{-- Section 3: Password --}}
        <x-ui.card title="Change Password" subtitle="Leave blank to keep current password">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <x-ui.input type="password" label="Current Password" name="current_password" />
                </div>
                <x-ui.input type="password" label="New Password" name="new_password" />
                <x-ui.input type="password" label="Confirm New Password" name="new_password_confirmation" />
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end">
            <x-ui.button type="submit" variant="primary">
                Save Changes
            </x-ui.button>
        </div>

    </form>
</x-app-layout>
