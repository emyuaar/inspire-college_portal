<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Learner Portal') - DirectSkills</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100">

    {{-- ================= HEADER ================ --}}
    <header class="bg-white border-b border-slate-200">
        <div class="w-full px-6 py-3 flex items-center justify-between">

            {{-- Left: Logo + Main Nav --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('portal.learner.dashboard') }}" class="flex items-center gap-2">
                    <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" class="h-7" alt="DirectSkills">
                    <span class="text-sm text-slate-600 font-medium">
                        Learner Portal
                    </span>
                </a>

                {{-- Top nav (like Moodle: Home / Dashboard / My courses) --}}
                <nav class="hidden md:flex items-center gap-4 text-sm">
                    <a href="{{ route('portal.learner.dashboard') }}" class="text-slate-700 hover:text-slate-900">
                        Home
                    </a>
                    <a href="{{ route('portal.learner.courses.all')}}" class="text-slate-700 hover:text-slate-900">
                        My Courses
                    </a>
                    {{-- future links --}}
                    {{-- <a href="#" class="text-slate-600 hover:text-slate-900">My courses</a> --}}
                </nav>
            </div>

            {{-- Right: User dropdown --}}
            @php
                $authUser = Auth::user();
                $initials = strtoupper(
                    mb_substr($authUser->first_name ?? '', 0, 1) . mb_substr($authUser->sur_name ?? '', 0, 1),
                );
            @endphp

            <div class="relative">
                <button id="userMenuButton" type="button"
                    class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2 py-1 hover:bg-slate-100">
                    <span class="hidden sm:inline text-sm text-slate-700">
                        {{ $authUser->first_name }} {{ $authUser->sur_name }}
                    </span>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-semibold text-white">
                        {{ $initials }}
                    </span>
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- FIXED DROPDOWN --}}
                <div id="userMenu"
                    class="hidden absolute top-full mt-2 right-0 min-w-[180px] rounded-md border border-slate-200 bg-white shadow-xl text-sm z-20">

                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="font-medium text-slate-800">
                            {{ $authUser->first_name }} {{ $authUser->sur_name }}
                        </p>
                        <p class="text-xs text-slate-500">{{ $authUser->email_address }}</p>
                    </div>

                    {{-- My Profile (View/Edit Account Settings) --}}
                    <a href="{{ route('portal.settings.profile') }}"
                    class="block px-4 py-2 text-slate-700 hover:bg-slate-50">
                        My Profile
                    </a>

                    <form method="POST" action="{{ route('portal.logout') }}" class="border-t border-slate-100">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- ================= MAIN CONTENT WRAPPER ================ --}}
    <main class="w-full px-6 py-6">

        {{-- Page Title (always left aligned) --}}
        @hasSection('page-title')
            <h1 class="text-xl font-semibold text-slate-800 mb-4">
                @yield('page-title')
            </h1>
        @endif

        {{-- MAIN PAGE CONTENT --}}
        @yield('content')

    </main>

    {{-- Simple dropdown JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('userMenuButton');
            const menu = document.getElementById('userMenu');

            if (!btn || !menu) return;

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', function() {
                menu.classList.add('hidden');
            });
        });
    </script>
</body>

</html>
