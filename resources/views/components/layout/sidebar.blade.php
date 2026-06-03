@props(['active' => 'dashboard'])

@php
    $nav = [
        'dashboard' => [
            'label' => 'Dashboard',
            'route' => 'portal.learner.dashboard',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />'
        ],
        'courses' => [
            'label' => 'My Courses',
            'route' => 'portal.learner.courses.all', 
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />'
        ],
        'profile' => [
            'label' => 'My Profile',
            'route' => 'portal.settings.profile',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />'
        ],
        // Add more as needed
    ];
@endphp

{{-- Sidebar Container --}}
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-ds-navy text-white transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0 lg:static lg:block shadow-xl lg:shadow-none">
    
    {{-- Branding --}}
    <div class="h-16 flex items-center justify-center pl-0 pr-6 border-b border-white/10 bg-[#00203a]">
        <img src="{{ asset('images/1000ppi/logo-white.png') }}" alt="Inspire College" class="h-12">
        {{-- Close button for mobile --}}
        <button id="closeSidebar" class="lg:hidden ml-auto text-white/70 hover:text-white">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Nav Links --}}
    <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">
        @foreach($nav as $key => $item)
            @php
                $isActive = $active === $key || request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route']) }}" 
               class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors duration-200 {{ $isActive ? 'bg-ds-pink text-white shadow-lg shadow-pink-900/20' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <div class="pt-4 mt-4 border-t border-white/10"></div>
        
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            {{-- Logout --}}
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-300 hover:bg-white/5 hover:text-red-200 transition-colors">
                 <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Sign Out</span>
            </button>
        </form>
    </nav>
</aside>

{{-- Mobile Overlay --}}
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0"></div>

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

        if(closeBtn) closeBtn.addEventListener('click', close);
        if(overlay) overlay.addEventListener('click', close);

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
