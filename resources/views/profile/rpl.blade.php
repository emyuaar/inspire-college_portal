<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RPL Information - DirectSkills Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" class="h-7" alt="DirectSkills">
                <span class="text-sm text-slate-500 hidden sm:inline">Learner Portal</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600">
                    {{ $user->first_name }} {{ $user->sur_name }}
                </span>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-xs px-3 py-1.5 rounded-full bg-red-500 text-white hover:bg-red-600">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h1 class="text-xl font-semibold text-slate-800">RPL Information</h1>
                <p class="text-sm text-slate-500">
                    If you do not have any prior learning / qualifications to declare at this stage,
                    please confirm below.
                </p>

                <form method="POST" action="{{ route('portal.profile.rpl.update') }}" class="space-y-4">
                    @csrf

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="confirm_no_rpl" value="1"
                               class="mt-1">
                        <span>
                            I confirm that I <strong>do not have any RPL (Recognition of Prior Learning) information</strong> to submit at this time.
                        </span>
                    </label>

                    @error('confirm_no_rpl')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <a href="{{ route('portal.learner.dashboard') }}"
                           class="text-sm text-slate-500 hover:text-slate-700">
                            ← Back to dashboard
                        </a>
                        <button type="submit"
                                class="px-5 py-2 rounded-full bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                            Save RPL Information
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
