@props(['active' => 'dashboard'])

@php
    $nav = [
        'dashboard' => [
            'label' => 'Dashboard',
            'route' => 'partner.dashboard',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'
        ],
        'learners' => [
            'label' => 'My Learners',
            'route' => 'partner.learners.index',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
        ],
        'pending_plans' => [
            'label' => 'Review Plans',
            'route' => 'partner.enrolments.pending_plans',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />'
        ],
        'installments' => [
            'label' => 'Installments',
            'route' => 'partner.installments.index',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />'
        ],
        'transactions' => [
            'label' => 'Payments',
            'route' => 'partner.transactions.index',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
        ],
        'create_learner' => [
            'label' => 'Create Learner',
            'route' => 'partner.learners.create',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />'
        ],
        'courses' => [
            'label' => 'My Courses',
            'route' => 'partner.courses.index',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />'
        ],
        'notifications' => [
            'label' => 'Notifications',
            'route' => 'partner.notifications.index',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />'
        ],
        'support' => [
            'label' => 'Support Desk',
            'route' => 'partner.support.index',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />'
        ],
        // Add more as needed
    ];
@endphp

{{-- Sidebar Container --}}
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-ds-navy text-white transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0 lg:static lg:block shadow-xl lg:shadow-none">

    {{-- Branding --}}
    <div class="h-16 flex items-center justify-center pl-0 pr-6 border-b border-white/10 bg-[#00203a]">
        <img src="https://inspirecollegeoflearning.com/storage/site/wWEQv28wcsGRQdL8Al3WkBzVkYY5mWr0jmC68Ddo.webp" alt="Inspire College" class="h-12">
        {{-- Close button for mobile --}}
        <button id="closeSidebar" class="lg:hidden ml-auto text-white/70 hover:text-white">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Nav Links --}}
    <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">

        <div class="px-3 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Partner Portal
        </div>

        @foreach($nav as $key => $item)
            @php
                $isActive = $active === $key || request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors duration-200 {{ $isActive ? 'bg-ds-pink text-white shadow-lg shadow-pink-900/20' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <div class="pt-4 mt-4 border-t border-white/10"></div>

        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            {{-- Logout --}}
            <button type="submit"
                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-300 hover:bg-white/5 hover:text-red-200 transition-colors">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Sign Out</span>
            </button>
        </form>
    </nav>
</aside>

{{-- Mobile Overlay --}}
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('closeSidebar');

        // Function to close sidebar
        const close = () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        };

        if (closeBtn) closeBtn.addEventListener('click', close);
        if (overlay) overlay.addEventListener('click', close);

        // Expose open function globally or listen to event
        window.openSidebar = () => {
            overlay.classList.remove('hidden');
            // force reflow
            overlay.offsetWidth;
            overlay.classList.remove('opacity-0');
            sidebar.classList.remove('-translate-x-full');
        };
    });
</script>