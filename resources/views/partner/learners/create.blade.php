@extends('layouts.partner')

@section('title', 'Create Learner')
@section('active-page', 'create_learner')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    
    <div class="flex items-center gap-4">
        <a href="{{ route('partner.learners.index') }}" class="group flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-ds-navy hover:border-ds-navy transition-all shadow-sm">
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Create New Learner</h1>
            <p class="text-sm text-slate-500">Add a new learner to your organization.</p>
        </div>
    </div>

    <x-ui.card padding="p-6 sm:p-8">
        <form action="{{ route('partner.learners.store') }}" method="POST" id="createLearnerForm">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                {{-- First Name --}}
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-slate-700 mb-1">First Name</label>
                    <input type="text" name="first_name" id="first_name" required value="{{ old('first_name') }}"
                        class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm"
                        placeholder="e.g. John">
                    @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Last Name --}}
                <div>
                    <label for="sur_name" class="block text-sm font-semibold text-slate-700 mb-1">Last Name</label>
                    <input type="text" name="sur_name" id="sur_name" required value="{{ old('sur_name') }}"
                        class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm"
                        placeholder="e.g. Doe">
                    @error('sur_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Email --}}
            <div class="mb-6">
                <label for="email_address" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email_address" id="email_address" required value="{{ old('email_address') }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm"
                    placeholder="learner@example.com">
                <p class="mt-1 text-xs text-slate-500">This will be the learner's login email.</p>
                @error('email_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <hr class="border-slate-100 my-6">

            <div class="mb-6">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Set Password</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" required
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm"
                            autocomplete="new-password">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm"
                            autocomplete="new-password">
                    </div>
                </div>

                {{-- Password Checklist --}}
                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm font-bold text-yellow-800 mb-2">🔒 Password Security Requirements:</p>
                    <ul class="text-xs space-y-2 text-slate-600" id="passwordRules">
                        <li id="rule-length" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            At least 8 characters
                        </li>
                        <li id="rule-upper" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            One uppercase letter
                        </li>
                        <li id="rule-lower" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            One lowercase letter
                        </li>
                        <li id="rule-number" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            One number
                        </li>
                        <li id="rule-symbol" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            One symbol (!@#$%^&*)
                        </li>
                        <li id="rule-match" class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px] bg-white"></span>
                            Passwords match
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <x-ui.button variant="primary" type="submit" id="submitBtn" disabled>
                    Create Learner
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pwd = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const submitBtn = document.getElementById('submitBtn');

        const rules = {
            length: val => val.length >= 8,
            upper: val => /[A-Z]/.test(val),
            lower: val => /[a-z]/.test(val),
            number: val => /[0-9]/.test(val),
            symbol: val => /[!@#$%^&*(),.?":{}|<>]/.test(val)
        };

        function updateStatus(id, isValid) {
            const el = document.getElementById('rule-' + id);
            const icon = el.querySelector('span');
            if (isValid) {
                el.classList.add('text-green-600');
                el.classList.remove('text-slate-500');
                icon.className = 'w-4 h-4 rounded-full bg-green-100 border border-green-200 text-green-600 flex items-center justify-center text-[10px] font-bold';
                icon.innerHTML = '✓';
            } else {
                el.classList.remove('text-green-600');
                el.classList.add('text-slate-500');
                icon.className = 'w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center text-[10px]';
                icon.innerHTML = '';
            }
        }

        function validate() {
            const val = pwd.value;
            const cVal = confirm.value;
            
            let allValid = true;

            // Check basic rules
            for (const [key, func] of Object.entries(rules)) {
                const isValid = func(val);
                updateStatus(key, isValid);
                if (!isValid) allValid = false;
            }

            // Check match
            const match = val && (val === cVal);
            updateStatus('match', match);
            if (!match) allValid = false;

            submitBtn.disabled = !allValid;
        }

        pwd.addEventListener('input', validate);
        confirm.addEventListener('input', validate);
    });
</script>
@endsection
