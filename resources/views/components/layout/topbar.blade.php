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
        <img src="https://inspirecollege.co.uk/images/Inspire College_logo.png" alt="Inspire College" class="h-8 sm:hidden">
    </div>

    {{-- Right Actions --}}
    <div class="flex items-center gap-4">
        {{-- Notifications Bell --}}
        @if(Auth::user() && Auth::user()->isOrganization())
        <div class="relative" x-data="notifBell()" x-init="fetchNotifs()" @click.outside="open = false">

            {{-- Bell Button --}}
            <button @click="toggle()"
                class="p-2 text-slate-400 hover:text-ds-navy hover:bg-slate-50 rounded-full transition-colors relative focus:outline-none"
                aria-label="Notifications">
                <i class="fa-solid fa-bell text-lg"></i>
                <span x-show="unread > 0" x-cloak
                    x-text="unread > 9 ? '9+' : unread"
                    class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-ds-pink text-white text-[9px] font-black rounded-full flex items-center justify-center ring-2 ring-white leading-none">
                </span>
            </button>

            {{-- Dropdown Panel --}}
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="absolute right-0 top-12 w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50 bg-slate-50/80">
                    <div>
                        <p class="font-black text-slate-800">Notifications</p>
                        <p class="text-[11px] text-slate-400 font-medium" x-text="unread + ' unread'"></p>
                    </div>
                    <div>
                        <button @click="markAllRead()" x-show="unread > 0" x-cloak
                            class="text-[11px] font-bold text-ds-pink hover:text-pink-700 transition-colors"
                            title="Mark all as read">
                            Mark all read
                        </button>
                    </div>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="flex items-center justify-center py-10">
                    <i class="fa-solid fa-spinner animate-spin text-slate-300 text-2xl"></i>
                </div>

                {{-- Notification Items --}}
                <div x-show="!loading" class="max-h-[420px] overflow-y-auto divide-y divide-slate-50" x-cloak>
                    <template x-if="notifications.length === 0">
                        <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                            <i class="fa-solid fa-bell-slash text-3xl text-slate-200 mb-3"></i>
                            <p class="font-bold text-slate-700">All clear!</p>
                            <p class="text-xs text-slate-400 mt-1">No new notifications right now.</p>
                        </div>
                    </template>

                    <template x-for="n in notifications" :key="n.id">
                        <a :href="'{{ url('partner/notifications') }}/' + n.id + '/read'"
                            class="flex items-start gap-4 px-5 py-4 hover:bg-slate-50 transition-colors group"
                            :class="n.unread ? 'bg-white' : 'bg-slate-50/30'">

                            {{-- Icon --}}
                            <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center mt-0.5"
                                :class="n.colors.bg">
                                <i :class="[n.icon, n.colors.text, 'text-sm']"></i>
                            </div>

                            {{-- Text --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 leading-tight flex items-center gap-1.5">
                                    <span x-text="n.title"></span>
                                    <span x-show="n.unread" class="inline-block w-1.5 h-1.5 rounded-full bg-ds-pink shrink-0"></span>
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-2" x-text="n.body"></p>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-[10px] text-slate-400" x-text="n.time"></span>
                                    <span x-show="n.action_label"
                                        class="text-[10px] font-bold text-ds-pink group-hover:underline"
                                        x-text="n.action_label + ' →'">
                                    </span>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- Panel Footer --}}
                <div x-show="!loading" x-cloak
                    class="px-5 py-3 border-t border-slate-100 bg-slate-50/80 text-center">
                    <a href="{{ route('partner.notifications.index') }}"
                        class="inline-block w-full py-2.5 text-xs font-bold text-ds-navy hover:text-white bg-slate-50 hover:bg-ds-navy border border-slate-200 rounded-xl transition-all duration-150 shadow-sm text-center">
                        View All
                    </a>
                </div>
            </div>
        </div>

        <script>
            function notifBell() {
                return {
                    open: false,
                    loading: false,
                    notifications: [],
                    unread: 0,

                    toggle() {
                        this.open = !this.open;
                        if (this.open) this.fetchNotifs();
                    },

                    fetchNotifs() {
                        this.loading = true;
                        fetch('{{ route('partner.notifications.dropdown') }}', {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        })
                        .then(r => r.json())
                        .then(data => {
                            this.notifications = data.notifications;
                            this.unread = data.unread_count;
                        })
                        .catch(() => {})
                        .finally(() => { this.loading = false; });
                    },

                    markAllRead() {
                        fetch('{{ route('partner.notifications.read_all') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        }).then(() => {
                            this.notifications = this.notifications.map(n => ({ ...n, unread: false }));
                            this.unread = 0;
                        });
                    }
                }
            }
        </script>
        @endif

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
