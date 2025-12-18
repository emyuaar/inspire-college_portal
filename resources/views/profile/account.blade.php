@extends('layouts.learner')

@section('title', 'Account Settings')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Flash message --}}
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-slate-900">Account Settings</h1>
                <a href="{{ route('portal.learner.dashboard') }}"
                   class="text-xs text-slate-500 hover:text-slate-700">
                    ← Back to dashboard
                </a>
            </div>

            <p class="text-xs text-slate-500 mb-5">
                Update your personal details and change your password.
            </p>

            <form method="POST" action="{{ route('portal.settings.profile.update') }}" class="space-y-8">
                @csrf

                {{-- PROFILE DETAILS --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-3">Profile details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-slate-500 mb-1">First Name</label>
                            <input type="text" name="first_name"
                                class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-slate-50 text-slate-400 cursor-not-allowed"
                                   value="{{ $user->first_name }}"
                                   disabled>
                            <input type="hidden" name="first_name" value="{{ $user->first_name }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Middle Name</label>
                            <input type="text" name="middle_name"
                                class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-slate-50 text-slate-400 cursor-not-allowed"
                                   value="{{ $user->middle_name }}"
                                   disabled>
                            <input type="hidden" name="middle_name" value="{{ $user->middle_name }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Surname</label>
                            <input type="text" name="sur_name"
                                class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-slate-50 text-slate-400 cursor-not-allowed"
                                   value="{{ $user->sur_name }}"
                                   disabled>
                            <input type="hidden" name="sur_name" value="{{ $user->sur_name }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Email Address</label>
                            <input type="email" name="email_address"
                                class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-slate-50 text-slate-400 cursor-not-allowed"
                                value="{{ $user->email_address }}"
                                disabled>
                        </div>
                    </div>
                </div>

                {{-- CONTACT + ADDRESS --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-3">Contact & Address</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-slate-500 mb-1">Contact Number</label>
                            <input type="text" name="contact"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('contact', $detail->contact ?? '') }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Date of Birth</label>
                            <input type="date" name="dob"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('dob', optional($detail)->dob ? \Carbon\Carbon::parse($detail->dob)->format('Y-m-d') : '') }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Gender</label>
                            <select name="gender"
                                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Select</option>
                                <option value="male"   @selected(old('gender', $detail->gender ?? '') === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $detail->gender ?? '') === 'female')>Female</option>
                                <option value="other"  @selected(old('gender', $detail->gender ?? '') === 'other')>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Country</label>
                            <input type="text" name="country"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('country', $detail->country ?? '') }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-slate-500 mb-1">Address Line 1</label>
                            <input type="text" name="address_line_1"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('address_line_1', $detail->address_line_1 ?? '') }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-slate-500 mb-1">Address Line 2</label>
                            <input type="text" name="address_line_2"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('address_line_2', $detail->address_line_2 ?? '') }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">City</label>
                            <input type="text" name="city"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('city', $detail->city ?? '') }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">State</label>
                            <input type="text" name="state"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('state', $detail->state ?? '') }}">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Postcode / ZIP</label>
                            <input type="text" name="zip_code"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                   value="{{ old('zip_code', $detail->zip_code ?? '') }}">
                        </div>
                    </div>
                </div>

                {{-- PASSWORD CHANGE --}}
                <div class="border-t border-slate-100 pt-6">
                    <h2 class="text-sm font-semibold text-slate-800 mb-3">Change password</h2>
                    <p class="text-xs text-slate-500 mb-4">
                        Leave this section blank if you do not want to change your password.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-slate-500 mb-1">Current password</label>
                            <input type="password" name="current_password"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">New password</label>
                            <input type="password" name="new_password"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Confirm new password</label>
                            <input type="password" name="new_password_confirmation"
                                   class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-5 py-2 rounded-full bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        Save changes
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
