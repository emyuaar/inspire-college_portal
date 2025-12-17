<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DirectSkills Portal Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center">

    <div class="w-full max-w-md px-4">
        <div class="bg-white shadow-lg rounded-2xl p-8 border border-slate-200">

            {{-- Logo + heading --}}
            <div class="text-center mb-6">
                <img src="https://directskills.co.uk/images/DirectSkills_logo.webp"
                     alt="DirectSkills"
                     class="h-12 mx-auto mb-2">
                <h1 class="text-xl font-semibold text-slate-800">Learner Portal</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Sign in to access your courses and progress.
                </p>
            </div>

            {{-- Error message --}}
            @if($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Login form --}}
            <form method="POST" action="{{ route('portal.login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                        Email address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm
                            placeholder:text-slate-400
                            focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm
                            placeholder:text-slate-400
                            focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
                    >
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 text-orange-500 focus:ring-orange-400"
                        >
                        <span class="text-slate-600">Remember me</span>
                    </label>

                    <a href="#" class="text-orange-500 hover:text-orange-600">
                        Forgot password?
                    </a>
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 rounded-lg
                           bg-gradient-to-r from-orange-500 to-amber-400 text-white text-sm
                           font-medium py-2.5 mt-2 shadow-md hover:shadow-lg
                           hover:from-orange-600 hover:to-amber-500 transition"
                >
                    Sign in
                </button>
            </form>

            <p class="mt-6 text-xs text-center text-slate-400">
                DirectSkills Learner Portal © {{ date('Y') }}
            </p>
        </div>
    </div>

</body>
</html>
