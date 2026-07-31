<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Learner Portal') - Inspire College</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* =========================
           MOBILE PWA SHELL (ONLY)
           Desktop must remain unchanged
           ========================= */

        :root {
            --ds-navy: #0F172A;
            --ds-pink: #2774AE;

            --pwa-bg: #f1f5f9;
            --pwa-card: #ffffff;
            --pwa-text: #0f172a;
            --pwa-muted: #64748b;

            --pwa-border: rgba(2, 6, 23, .08);
            --pwa-border-2: rgba(2, 6, 23, .06);

            --pwa-shadow: 0 14px 35px rgba(2, 6, 23, 0.08);
            --pwa-shadow-2: 0 10px 22px rgba(2, 6, 23, 0.08);
        }

        /* Give space for fixed mobile appbar + tabbar */
        @media (max-width: 767.98px) {
            body {
                background: var(--pwa-bg) !important;
            }

            .pwa-main {
                padding: 86px 14px 98px 14px !important;
            }

            /* hide desktop header on mobile */
            .ds-desktop-header {
                display: none !important;
            }

            /* show mobile shell */
            .ds-mobile-shell {
                display: block !important;
            }

            /* smooth fonts */
            body,
            button,
            a {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        }

        /* Desktop behaviour unchanged */
        @media (min-width: 768px) {
            .ds-mobile-shell {
                display: none !important;
            }
        }

        /* Safe-area for iOS (PWA) */
        .pwa-appbar,
        .pwa-tabbar {
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }

        .pwa-appbar {
            padding-top: env(safe-area-inset-top);
        }

        .pwa-tabbar {
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* ===== App Bar: premium look ===== */
        .pwa-appbar {
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--pwa-border);
        }

        .pwa-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--pwa-text);
            line-height: 1.1;
            letter-spacing: .2px;
        }

        .pwa-subtitle {
            font-size: 11px;
            color: var(--pwa-muted);
            margin-top: 2px;
            line-height: 1.1;
        }

        /* Avatar button */
        .pwa-avatar {
            height: 40px;
            width: 40px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-weight: 800;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(1, 52, 91, .12);
            box-shadow: 0 10px 20px rgba(2, 6, 23, .10);
            transition: transform .15s ease;
        }

        .pwa-avatar:active {
            transform: scale(.98);
        }

        /* ===== Bottom Tab Bar: floating style ===== */
        .pwa-tabbar {
            background: transparent;
            border-top: 0;
        }

        .pwa-tabwrap {
            margin: 10px 12px;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--pwa-border);
            border-radius: 18px;
            box-shadow: var(--pwa-shadow-2);
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 6px;
        }

        .pwa-tab {
            position: relative;
            border-radius: 14px;
            padding: 10px 0;
            transition: .15s ease;
            user-select: none;
        }

        .pwa-tab:active {
            transform: translateY(1px);
        }

        /* tab icon + label default */
        .pwa-tab svg {
            color: #64748b;
        }

        .pwa-tab span {
            color: #64748b;
            font-weight: 700;
        }

        /* active: pill highlight + small bar */
        .pwa-tab.is-active {
            background: rgba(1, 52, 91, .07);
        }

        .pwa-tab.is-active svg,
        .pwa-tab.is-active span {
            color: var(--ds-navy);
        }

        .pwa-tab.is-active::after {
            content: "";
            position: absolute;
            bottom: 6px;
            left: 50%;
            width: 18px;
            height: 3px;
            transform: translateX(-50%);
            border-radius: 999px;
            background: var(--ds-navy);
            opacity: .9;
        }

        /* ===== User sheet (bottom action sheet) ===== */
        .pwa-sheet {
            background: #fff;
            border: 1px solid var(--pwa-border);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            box-shadow: 0 -20px 60px rgba(2, 6, 23, .18);
        }

        .pwa-sheet-handle {
            width: 44px;
            height: 5px;
            background: rgba(2, 6, 23, .12);
            border-radius: 999px;
            margin: 6px auto 12px;
        }

        .pwa-sheet-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--pwa-border-2);
            background: rgba(2, 6, 23, .02);
            color: #0f172a;
            font-weight: 800;
            transition: .15s ease;
        }

        .pwa-sheet-item:hover {
            background: rgba(1, 52, 91, .06);
            border-color: rgba(1, 52, 91, .12);
        }

        .pwa-sheet-danger {
            color: #dc2626;
            background: rgba(220, 38, 38, .06);
            border-color: rgba(220, 38, 38, .18);
        }

        .pwa-sheet-danger:hover {
            background: rgba(220, 38, 38, .10);
        }

        /* tabbar should be below sheet */
        .pwa-tabbar{
            z-index: 60;
        }

        /* sheet overlay must be above everything */
        #pwaUserMenu .fixed{
            z-index: 9999;
        }

        /* sheet bottom padding safe-area + tabbar space */
        .pwa-sheet{
            padding-bottom: calc(18px + env(safe-area-inset-bottom) + 70px);
            max-height: calc(100vh - 90px);
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 22px 22px 0 0;   /* bottom attached */
            margin: 0;
        }

        .pwa-sheet-item{
            background:#fff;
            border: 1px solid rgba(2,6,23,.10);
            box-shadow: 0 8px 18px rgba(2,6,23,.06);
        }

        .pwa-sheet-danger{
            background: rgba(220,38,38,.06);
            border-color: rgba(220,38,38,.20);
            box-shadow: none;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-100">

    {{-- =========================================================
        MOBILE PWA SHELL (ONLY on < md)
        - App Bar (fixed)
        - Bottom Tab Bar (fixed)
        Desktop par kuch change nahi hoga
    ========================================================== --}}
    <div class="ds-mobile-shell hidden">

        {{-- Mobile App Bar --}}
        <header class="pwa-appbar fixed top-0 left-0 right-0 z-50">
            <div class="h-14 px-3 flex items-center justify-between">

                {{-- Left: Dynamic page title --}}
                <div class="flex items-center min-w-0">
                    <div class="min-w-0">
                        @php
                            $defaultTitle =
                                request()->routeIs('portal.learner.dashboard') ? 'Learner Portal' :
                                (request()->routeIs('portal.learner.courses.*') ? 'My Courses' :
                                (request()->routeIs('portal.settings.*') ? 'My Profile' : 'Learner Portal'));

                            $pwaTitle = trim($__env->yieldContent('pwa-title')) ?: $defaultTitle;
                            $pwaSub = trim($__env->yieldContent('pwa-subtitle'));
                        @endphp

                        <div class="pwa-title truncate">{{ $pwaTitle }}</div>

                        @if($pwaSub !== '')
                            <div class="pwa-subtitle truncate">{{ $pwaSub }}</div>
                        @endif
                    </div>
                </div>

                {{-- Right: avatar --}}
                @php
                    $authUser = Auth::user();
                    $initials = strtoupper(
                        mb_substr($authUser->first_name ?? '', 0, 1) . mb_substr($authUser->sur_name ?? '', 0, 1),
                    );
                @endphp

                <button id="pwaUserBtn" type="button" class="pwa-avatar">
                    {{ $initials }}
                </button>
            </div>

        </header>
            {{-- Mobile quick user menu (BOTTOM SHEET) - OUTSIDE HEADER to avoid clipping --}}
            <div id="pwaUserMenu" class="hidden">
                <div class="fixed inset-0 z-[9999]">
                    <div class="absolute inset-0 bg-black/40" data-close-pwa-menu></div>

                    <div class="absolute bottom-0 left-0 right-0 pwa-sheet p-4">
                        <div class="pwa-sheet-handle"></div>

                        <div class="flex items-center gap-3 mb-3">
                            <div class="pwa-avatar">{{ $initials }}</div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-slate-900 truncate">
                                    {{ $authUser->first_name }} {{ $authUser->sur_name }}
                                </div>
                                <div class="text-xs text-slate-500 truncate">{{ $authUser->email_address }}</div>
                            </div>
                        </div>

                        <a href="{{ route('portal.settings.profile') }}" class="pwa-sheet-item">
                            My Profile
                        </a>

                        <form method="POST" action="{{ route('portal.logout') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="pwa-sheet-item pwa-sheet-danger">
                                Log out
                            </button>
                        </form>

                        {{-- <button type="button" data-close-pwa-menu class="mt-3 w-full pwa-sheet-item">
                            Close
                        </button> --}}
                    </div>
                </div>
            </div>
        {{-- Mobile Bottom Tab Bar --}}
        <nav class="pwa-tabbar fixed bottom-0 left-0 right-0 z-50">
            <div class="pwa-tabwrap">

                @php
                    $path = request()->path();
                    $isDashboard = request()->routeIs('portal.learner.dashboard');
                    $isCourses = request()->routeIs('portal.learner.courses.*') || str_contains($path, 'courses');
                    $isProfile = request()->routeIs('portal.settings.*');
                @endphp

                <a href="{{ route('portal.learner.dashboard') }}"
                   class="pwa-tab {{ $isDashboard ? 'is-active' : '' }} flex-1 flex flex-col items-center justify-center gap-1 py-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10z" />
                    </svg>
                    <span class="text-[11px]">Home</span>
                </a>

                <a href="{{ route('portal.learner.courses.all') }}"
                   class="pwa-tab {{ $isCourses ? 'is-active' : '' }} flex-1 flex flex-col items-center justify-center gap-1 py-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                    <span class="text-[11px]">Courses</span>
                </a>

                <a href="{{ route('portal.settings.profile') }}"
                   class="pwa-tab {{ $isProfile ? 'is-active' : '' }} flex-1 flex flex-col items-center justify-center gap-1 py-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
                    </svg>
                    <span class="text-[11px]">Profile</span>
                </a>

            </div>
        </nav>
    </div>


    {{-- ================= DESKTOP HEADER (UNCHANGED) ================ --}}
    <header class="ds-desktop-header bg-white border-b border-slate-200">
        <div class="w-full px-6 py-3 flex items-center justify-between">

            {{-- Left: Logo + Main Nav --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('portal.learner.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/1000ppi/logo.png') }}" class="h-7" alt="Inspire College">
                    <span class="text-sm text-slate-600 font-medium">
                        Learner Portal
                    </span>
                </a>

                {{-- Top nav (like Moodle: Home / Dashboard / My courses) --}}
                <nav class="hidden md:flex items-center gap-4 text-sm">
                    <a href="{{ route('portal.learner.dashboard') }}" class="text-slate-700 hover:text-slate-900">
                        Home
                    </a>
                    <a href="{{ route('portal.learner.courses.all') }}" class="text-slate-700 hover:text-slate-900">
                        My Courses
                    </a>
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

                <div id="userMenu"
                    class="hidden absolute top-full mt-2 right-0 min-w-[180px] rounded-md border border-slate-200 bg-white shadow-xl text-sm z-20">

                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="font-medium text-slate-800">
                            {{ $authUser->first_name }} {{ $authUser->sur_name }}
                        </p>
                        <p class="text-xs text-slate-500">{{ $authUser->email_address }}</p>
                    </div>

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
    <main class="w-full px-6 py-6 pwa-main">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success'))
                <x-ui.alert variant="success" dismissible title="Success">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            @if(session('error'))
                <x-ui.alert variant="error" dismissible title="Error">
                    {{ session('error') }}
                </x-ui.alert>
            @endif
        </div>

        @hasSection('page-title')
            <h1 class="text-xl font-semibold text-slate-800 mb-4">
                @yield('page-title')
            </h1>
        @endif

        @yield('content')
    </main>

    {{-- Dropdown JS (desktop) + PWA sheet (mobile) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // desktop dropdown
            const btn = document.getElementById('userMenuButton');
            const menu = document.getElementById('userMenu');

            if (btn && menu) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                });
                document.addEventListener('click', function() {
                    menu.classList.add('hidden');
                });
            }

            // mobile user sheet
            const pwaBtn = document.getElementById('pwaUserBtn');
            const pwaMenu = document.getElementById('pwaUserMenu');

            if (pwaBtn && pwaMenu) {
                const close = () => pwaMenu.classList.add('hidden');

                pwaBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    pwaMenu.classList.toggle('hidden');
                });

                pwaMenu.addEventListener('click', (e) => {
                    const closeEl = e.target.closest('[data-close-pwa-menu]');
                    if (closeEl) close();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') close();
                });
            }
        });
    </script>

    <script>
        window.LearnerLogger = {
            // Core context to be populated by Blade
            pageContext: {
                url: window.location.href,
                referrer: document.referrer,
                course_id: @json($course->id ?? $enrolment->course_id ?? null),
                module_id: @json($module->id ?? null),
                lesson_id: @json($lesson->id ?? null),
                assignment_id: @json($assignment->id ?? null),
                correlation_id: '{{ app()->bound("learner_request_correlation_id") ? app("learner_request_correlation_id") : "" }}'
            },
            
            log: function(event_type, event_category = 'frontend', extra = {}) {
                let payload = {
                    event_type: event_type,
                    event_category: event_category,
                    _token: '{{ csrf_token() }}',
                    // Auto-include page context
                    ...this.pageContext,
                    // Additional specific data
                    device_type: window.innerWidth < 768 ? 'mobile' : 'desktop',
                    network_state: navigator.onLine ? 'online' : 'offline',
                    ...extra
                };
                
                fetch('{{ route("portal.learner.diagnostic.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Correlation-ID': this.pageContext.correlation_id
                    },
                    body: JSON.stringify(payload)
                }).catch(e => console.error('LearnerLogger push failed', e));
            },
            
            error: function(msg, stack = null, extra = {}) {
                this.log('learner.frontend.js_error', 'error', {
                    message_human: 'Frontend Javascript Error',
                    message_technical: msg,
                    frontend_error_stack: stack,
                    severity: 'error',
                    action_status: 'failed',
                    ...extra
                });
            },

            interaction: function(action, category = 'navigation', extra = {}) {
                this.log('learner.interaction.' + action, category, {
                    action_attempted: action,
                    ...extra
                });
            }
        };

        // Track external lesson links
        document.addEventListener('click', function(e) {
            const lessonLink = e.target.closest('a[data-log-click]');
            if (lessonLink) {
                const action = lessonLink.dataset.logAction || 'click';
                const label = lessonLink.dataset.logLabel || lessonLink.innerText.trim();
                window.LearnerLogger.interaction(action, 'interaction', {
                    message_human: 'Learner clicked: ' + label,
                    technical_details: lessonLink.href
                });
            }
        });

        // Window error tracking
        window.addEventListener('error', function(event) {
            window.LearnerLogger.error(event.message || 'Unknown error', event.error ? event.error.stack : null, { 
                file_name: event.filename,
                line_number: event.lineno,
                column_number: event.colno
            });
        });

        window.addEventListener('unhandledrejection', function(event) {
            let msg = event.reason ? (event.reason.message || event.reason) : 'Promise rejection';
            let stack = event.reason && event.reason.stack ? event.reason.stack : null;
            window.LearnerLogger.error(msg, stack, { message_human: 'Unhandled Promise Rejection' });
        });

        // Intercept fetch errors globally
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            try {
                const response = await originalFetch.apply(this, args);
                if (!response.ok && !args[0].toString().includes('/learner/diagnostic-logs')) {
                    window.LearnerLogger.log('learner.ajax.request.failed', 'error', {
                        message_human: 'Fetch request failed (' + response.status + ')',
                        message_technical: 'URL: ' + args[0],
                        severity: response.status >= 500 ? 'error' : 'warning',
                        action_status: 'failed',
                        response_status_code: response.status
                    });
                }
                return response;
            } catch (err) {
                if (!args[0].toString().includes('/learner/diagnostic-logs')) {
                    window.LearnerLogger.error('Network/CORS error: ' + err.message, err.stack, { 
                        message_human: 'Fetch failed to execute', 
                        url: args[0],
                        event_category: 'network'
                    });
                }
                throw err;
            }
        };

        // jQuery AJAX error tracking
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (settings.url && !settings.url.includes('/learner/diagnostic-logs')) {
                    window.LearnerLogger.log('learner.ajax.request.failed', 'error', {
                        message_human: 'AJAX request failed',
                        message_technical: 'URL: ' + settings.url + ' | Error: ' + thrownError,
                        severity: jqxhr.status >= 500 ? 'error' : 'warning',
                        action_status: 'failed',
                        response_status_code: jqxhr.status
                    });
                }
            });
        }
    </script>
</body>

</html>
