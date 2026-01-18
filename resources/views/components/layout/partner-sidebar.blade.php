@props(['active' => 'dashboard'])

@php
    $nav = [
        'dashboard' => [
            'label' => 'My Learners',
            'route' => 'partner.learners.index',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
        ],
        'create_learner' => [
            'label' => 'Create Learner',
            'route' => 'partner.learners.create',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />'
        ],
        // Add more as needed
    ];
@endphp

{{-- Sidebar Container --}}
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-ds-navy text-white transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0 lg:static lg:block shadow-xl lg:shadow-none">
    
    {{-- Branding --}}
    <div class="h-16 flex items-center justify-center pl-0 pr-6 border-b border-white/10 bg-[#00203a]">
        <img src="https://directskills.co.uk/images/DirectSKills%20inverted-02.png" alt="DirectSkills" class="h-12">
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
