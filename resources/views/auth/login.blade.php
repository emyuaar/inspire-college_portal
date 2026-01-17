<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - DirectSkills Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ds-navy: #01345B;
            --ds-pink: #A91A6A;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Custom focus ring for brand alignment */
        .ds-input:focus {
            outline: none;
            border-color: var(--ds-pink);
            box-shadow: 0 0 0 4px rgba(169, 26, 106, 0.1);
        }

        /* Checkbox custom style */
        .ds-checkbox {
            color: var(--ds-pink);
            border-radius: 4px;
            border-color: #cbd5e1;
        }

        .ds-checkbox:focus {
            box-shadow: 0 0 0 2px rgba(169, 26, 106, 0.2);
        }
    </style>
</head>

<body class="min-h-screen bg-white md:bg-slate-50 flex">

    {{-- LEFT COLUMN: BRANDING (Desktop Only) --}}
    <div
        class="hidden md:flex md:w-1/2 lg:w-[45%] bg-[#01345B] relative overflow-hidden flex-col justify-between p-12 text-white">

        {{-- Background Pattern/Effect --}}
        <div class="absolute inset-0 opacity-10"
            style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;">
        </div>

        {{-- Decorative Circle --}}
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-[#A91A6A] rounded-full blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-[#00203a] to-transparent"></div>

        {{-- Content --}}
        <div class="relative z-10">
            <img src="https://directskills.co.uk/images/DirectSKills%20inverted-02.png" alt="DirectSkills"
                class="h-10 mb-8">

            <h1 class="text-4xl font-bold leading-tight mb-4">
                Welcome to your <br>
                <span class="text-[#A91A6A] text-pink-400">Learning Portal</span>
            </h1>
            <p class="text-slate-300 text-lg max-w-md leading-relaxed">
                Access your courses, track your progress, and manage your assignments all in one place.
            </p>
        </div>

        <div class="relative z-10 text-sm text-slate-400">
            &copy; {{ date('Y') }} DirectSkills. All rights reserved.
        </div>
    </div>

    {{-- RIGHT COLUMN: LOGIN FORM --}}
    <div class="w-full md:w-1/2 lg:w-[55%] flex flex-col items-center justify-center p-6 md:p-12 bg-white relative">

        <div class="w-full max-w-[420px] space-y-8">

            {{-- Mobile Logo (Visible only on mobile) --}}
            <div class="md:hidden text-center mb-8">
                <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" alt="DirectSkills"
                    class="h-10 mx-auto">
            </div>

            {{-- Header --}}
            <div class="text-center md:text-left">
                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Sign in</h2>
                <p class="text-slate-500 mt-2 text-sm">
                    Enter your credentials to access your account.
                </p>
            </div>

            {{-- Alerts / Errors --}}
            @if($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-100 p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-red-700 font-medium">
                        {{ $errors->first() }}
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('portal.login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="name@example.com"
                        class="ds-input block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:bg-white transition-all text-[16px] sm:text-sm shadow-sm"
                        style="min-height: 48px;"> {{-- Tap friendly height --}}
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-semibold text-slate-700">
                            Password
                        </label>
                        {{-- Forgot Password Link --}}
                        <a href="#" class="text-sm font-semibold text-[#01345B] hover:text-[#A91A6A] transition-colors">
                            Forgot password?
                        </a>
                    </div>
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                        class="ds-input block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:bg-white transition-all text-[16px] sm:text-sm shadow-sm"
                        style="min-height: 48px;">
                </div>

                <div class="flex items-center pt-1">
                    <input id="remember" name="remember" type="checkbox"
                        class="ds-checkbox h-5 w-5 rounded border-slate-300 text-[#A91A6A] focus:ring-[#A91A6A]">
                    <label for="remember" class="ml-3 block text-sm text-slate-600">
                        Remember me for 30 days
                    </label>
                </div>

                <button type="submit"
                    class="w-full flex items-center justify-center rounded-full bg-[#A91A6A] py-3.5 px-4 text-sm font-bold text-white shadow-lg shadow-pink-900/10 hover:bg-[#8f165a] hover:shadow-xl hover:scale-[1.01] active:scale-[0.98] transition-all duration-200">
                    Sign in to Portal
                </button>
            </form>

            {{-- Footer Text --}}
            <p class="text-center text-xs text-slate-400 mt-8">
                By signing in, you agree to our <a href="#" class="underline hover:text-slate-600">Terms of Service</a>
                and <a href="#" class="underline hover:text-slate-600">Privacy Policy</a>.
            </p>
        </div>
    </div>

</body>

</html>