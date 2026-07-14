{{-- SET PASSWORD VIEW DEBUG: auth/set-password.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($flow ?? 'account_setup') === 'account_setup' ? 'Set up your account' : 'Reset your password' }} - DirectSkills</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .ds-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        
        .ds-password-rules {
            margin: 12px 0 22px;
            padding: 15px 16px;
            border-radius: 14px;
            background: #f4f8ff;
            border: 1px solid #dbe7f7;
        }

        .ds-password-rules-title {
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 800;
            color: #01345B;
        }

        .ds-password-rules ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ds-password-rules li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 7px;
            font-size: 13px;
            color: #7b8ba3;
            transition: color 0.2s;
        }

        .ds-password-rules li::before {
            content: "○";
            position: absolute;
            left: 0;
            color: #9aa8bc;
            font-weight: 800;
            transition: color 0.2s;
        }

        .ds-password-rules li.valid {
            color: #0f8a4c;
            font-weight: 700;
        }

        .ds-password-rules li.valid::before {
            content: "✓";
            color: #0f8a4c;
        }

        .ds-password-rules li.invalid {
            color: #b42318;
        }

        .ds-password-rules li.invalid::before {
            content: "×";
            color: #b42318;
        }

        button[disabled],
        button.disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="mb-4">
                <img src="https://directskills.co.uk/images/DirectSkills_logo.png" alt="DirectSkills" class="h-10 mx-auto">
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ ($flow ?? 'account_setup') === 'account_setup' ? 'Set up your account' : 'Reset your password' }}</h1>
            <p class="text-slate-500 mt-2">{{ ($flow ?? 'account_setup') === 'account_setup' ? 'Create a secure password to access your course.' : 'Choose a new secure password for your account.' }}</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="p-8">
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl">
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" id="passwordForm">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $accountEmail }}">
                    <input type="hidden" name="flow" value="{{ $flow ?? 'account_setup' }}">

                    {{-- Email Display (Read-only) --}}
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Account Email</label>
                        <input 
                            type="email"
                            value="{{ $accountEmail }}"
                            readonly
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-600 font-medium outline-none cursor-not-allowed"
                        >
                    </div>

                    {{-- Password --}}
                    <div class="mb-6">
                        <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">New Password</label>
                        <input type="password" id="password" name="password" required autofocus
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                               placeholder="••••••••">
                    </div>

                    {{-- Password Rules Checklist --}}
                    <div class="ds-password-rules" id="passwordRules">
                        <div class="ds-password-rules-title">Password requirements</div>
                        <ul>
                            <li id="ruleLength">At least 8 characters</li>
                            <li id="ruleUpper">One uppercase letter</li>
                            <li id="ruleLower">One lowercase letter</li>
                            <li id="ruleNumber">One number</li>
                            <li id="ruleSymbol">One special character</li>
                            <li id="ruleMatch">Passwords match</li>
                        </ul>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                               placeholder="••••••••">
                    </div>

                    <button type="submit" id="submitBtn" disabled
                            class="w-full py-4 ds-gradient text-white rounded-2xl font-bold shadow-lg shadow-blue-900/20 transition-all opacity-50 cursor-not-allowed">
                        {{ ($flow ?? 'account_setup') === 'account_setup' ? 'Complete Account Setup' : 'Reset Password' }}
                    </button>
                </form>
            </div>
            
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400 font-medium">{{ ($flow ?? 'account_setup') === 'account_setup' ? 'Secure Account Activation' : 'Secure Password Reset' }} • DirectSkills Portal</p>
            </div>
        </div>
        
        <p class="text-center mt-8 text-slate-400 text-sm">
            Need help? Contact <a href="mailto:support@directskills.co.uk" class="text-slate-600 font-bold hover:underline">Support</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="password_confirmation"]');
            const submitBtn = document.querySelector('button[type="submit"]');

            const ruleLength = document.getElementById('ruleLength');
            const ruleUpper = document.getElementById('ruleUpper');
            const ruleLower = document.getElementById('ruleLower');
            const ruleNumber = document.getElementById('ruleNumber');
            const ruleSymbol = document.getElementById('ruleSymbol');
            const ruleMatch = document.getElementById('ruleMatch');

            function setRule(el, valid) {
                if (!el) return;
                el.classList.remove('valid', 'invalid');
                el.classList.add(valid ? 'valid' : 'invalid');
            }

            function validatePasswordRules() {
                const value = password ? password.value : '';
                const confirmValue = confirmPassword ? confirmPassword.value : '';

                const checks = {
                    length: value.length >= 8,
                    upper: /[A-Z]/.test(value),
                    lower: /[a-z]/.test(value),
                    number: /[0-9]/.test(value),
                    symbol: /[^A-Za-z0-9]/.test(value),
                    match: value.length > 0 && value === confirmValue,
                };

                setRule(ruleLength, checks.length);
                setRule(ruleUpper, checks.upper);
                setRule(ruleLower, checks.lower);
                setRule(ruleNumber, checks.number);
                setRule(ruleSymbol, checks.symbol);
                setRule(ruleMatch, checks.match);

                const allValid = Object.values(checks).every(Boolean);

                if (submitBtn) {
                    submitBtn.disabled = !allValid;
                    submitBtn.classList.toggle('disabled', !allValid);
                    if (allValid) {
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.classList.add('hover:scale-[1.02]', 'active:scale-[0.98]');
                    } else {
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        submitBtn.classList.remove('hover:scale-[1.02]', 'active:scale-[0.98]');
                    }
                }
            }

            if (password) password.addEventListener('input', validatePasswordRules);
            if (confirmPassword) confirmPassword.addEventListener('input', validatePasswordRules);

            validatePasswordRules();
        });
    </script>
</body>
</html>
