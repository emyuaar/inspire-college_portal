@props([
    'title' => 'Portal',
    'userRole' => 'Learner',
])

<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 z-30 sticky top-0">
    
    <div class="flex items-center gap-4">
        {{-- Mobile Menu Trigger --}}
        <button onclick="window.openSidebar()" type="button" class="lg:hidden p-2 -ml-2 text-slate-500 hover:bg-slate-100 rounded-md">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        {{-- Page Title --}}
        <h1 class="text-lg font-bold text-slate-800 hidden sm:block">
            {{ $title }}
        </h1>
        
        {{-- Mobile Logo --}}
        <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" alt="DirectSkills" class="h-8 sm:hidden">
    </div>

    {{-- Right Actions --}}
    <div class="flex items-center gap-4">
        {{-- Notifications (Placeholder) --}}
        <button class="p-2 text-slate-400 hover:text-ds-navy hover:bg-slate-50 rounded-full transition-colors relative">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="absolute top-2 right-2.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="h-6 w-px bg-slate-200 mx-1"></div>

        {{-- User Menu --}}
        @php
            $user = Auth::user();
            $initials = $user ? strtoupper(substr($user->first_name, 0, 1) . substr($user->sur_name, 0, 1)) : 'DS';
        @endphp
        
        <div class="relative group" tabindex="0">
            <button class="flex items-center gap-3 hover:bg-slate-50 rounded-full pl-1 pr-3 py-1 transition-colors border border-transparent hover:border-slate-100">
                <div class="h-9 w-9 bg-ds-navy text-white rounded-full flex items-center justify-center text-xs font-bold ring-2 ring-white shadow-sm">
                    {{ $initials }}
                </div>
                <div class="hidden md:block text-left">
                     <p class="text-sm font-bold text-slate-700 leading-none">{{ $user->first_name ?? 'User' }}</p>
                     <p class="text-[11px] text-slate-500 mt-0.5">{{ $userRole }}</p>
                </div>
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                 </svg>
            </button>

            {{-- Dropdown --}}
            <div class="absolute right-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-xl py-1 hidden group-focus-within:block group-hover:block z-50">
                <div class="px-4 py-2 border-b border-slate-50 md:hidden">
                    <p class="text-sm font-bold text-slate-800">{{ $user->first_name ?? 'User' }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $user->email_address ?? '' }}</p>
                </div>
                <a href="{{ $userRole === 'Partner' ? route('partner.profile.edit') : route('portal.settings.profile') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-ds-navy">
                    My Profile
                </a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-ds-navy">
                    Support
                </a>
                <div class="border-t border-slate-50 my-1"></div>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
