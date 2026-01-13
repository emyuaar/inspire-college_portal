<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Personal Information - DirectSkills Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root{
            --ds-navy:#01345b;
            --ds-pink:#a91a6a;

            --pwa-bg:#f1f5f9;
            --pwa-card:#ffffff;
            --pwa-text:#0f172a;
            --pwa-muted:#64748b;
            --pwa-border:rgba(2,6,23,.08);
            --pwa-shadow: 0 14px 35px rgba(2,6,23,0.08);
            --pwa-shadow-2: 0 10px 22px rgba(2,6,23,0.08);
        }

        /* =========================
           MOBILE PWA SHELL (ONLY)
           Desktop must remain unchanged
           ========================= */
        @media (max-width: 767.98px){
            body{ background: var(--pwa-bg) !important; }

            /* hide desktop header on mobile */
            .ds-desktop-header{ display:none !important; }

            /* show mobile shell */
            .ds-mobile-shell{ display:block !important; }

            /* content spacing for fixed appbar + tabbar */
            .pwa-main{
                padding: 86px 14px 98px 14px !important;
            }

            body, button, a, input, select{
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        }

        @media (min-width: 768px){
            .ds-mobile-shell{ display:none !important; }
        }

        /* Safe-area */
        .pwa-appbar, .pwa-tabbar{
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }
        .pwa-appbar{ padding-top: env(safe-area-inset-top); }
        .pwa-tabbar{ padding-bottom: env(safe-area-inset-bottom); }

        /* Appbar */
        .pwa-appbar{
            background: rgba(255,255,255,.88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--pwa-border);
        }
        .pwa-title{
            font-size: 14px;
            font-weight: 800;
            color: var(--pwa-text);
            line-height: 1.1;
            letter-spacing: .2px;
        }
        .pwa-subtitle{
            font-size: 11px;
            color: var(--pwa-muted);
            margin-top: 2px;
            line-height: 1.1;
        }

        .pwa-avatar{
            height: 40px; width: 40px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-weight: 800;
            font-size: 12px;
            display:flex; align-items:center; justify-content:center;
            border: 2px solid rgba(1,52,91,.12);
            box-shadow: 0 10px 20px rgba(2,6,23,.10);
            transition: transform .15s ease;
        }
        .pwa-avatar:active{ transform: scale(.98); }

        /* Tabbar floating */
        .pwa-tabbar{ background: transparent; border-top: 0; z-index: 60; }
        .pwa-tabwrap{
            margin: 10px 12px;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--pwa-border);
            border-radius: 18px;
            box-shadow: var(--pwa-shadow-2);
            height: 64px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding: 6px;
        }
        .pwa-tab{
            position: relative;
            border-radius: 14px;
            padding: 10px 0;
            transition: .15s ease;
            user-select:none;
        }
        .pwa-tab:active{ transform: translateY(1px); }
        .pwa-tab svg{ color: #64748b; }
        .pwa-tab span{ color: #64748b; font-weight: 700; }
        .pwa-tab.is-active{ background: rgba(1,52,91,.07); }
        .pwa-tab.is-active svg, .pwa-tab.is-active span{ color: var(--ds-navy); }
        .pwa-tab.is-active::after{
            content:"";
            position:absolute;
            bottom: 6px;
            left: 50%;
            width: 18px;
            height: 3px;
            transform: translateX(-50%);
            border-radius: 999px;
            background: var(--ds-navy);
            opacity:.9;
        }

        /* User sheet */
        #pwaUserMenu .fixed{ z-index: 9999; }
        .pwa-sheet{
            background:#fff;
            border: 1px solid var(--pwa-border);
            border-radius: 22px 22px 0 0;
            box-shadow: 0 -20px 60px rgba(2,6,23,.18);
            padding-bottom: calc(18px + env(safe-area-inset-bottom) + 70px);
            max-height: calc(100vh - 90px);
            overflow:auto;
            -webkit-overflow-scrolling: touch;
        }
        .pwa-sheet-handle{
            width: 44px; height: 5px;
            background: rgba(2,6,23,.12);
            border-radius: 999px;
            margin: 6px auto 12px;
        }
        .pwa-sheet-item{
            display:block;
            width:100%;
            text-align:left;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(2,6,23,.10);
            background: #fff;
            color: #0f172a;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(2,6,23,.06);
            transition:.15s ease;
        }
        .pwa-sheet-item:hover{ background: rgba(1,52,91,.06); border-color: rgba(1,52,91,.12); }
        .pwa-sheet-danger{
            color:#dc2626;
            background: rgba(220,38,38,.06);
            border-color: rgba(220,38,38,.20);
            box-shadow:none;
        }
        .pwa-sheet-danger:hover{ background: rgba(220,38,38,.10); }

        /* =========================
           FORM UI POLISH
           ========================= */
        .ds-card{
            background: var(--pwa-card);
            border: 1px solid var(--pwa-border);
            border-radius: 18px;
            box-shadow: var(--pwa-shadow);
        }
        .ds-sec{
            border: 1px solid rgba(2,6,23,.06);
            border-radius: 16px;
            background: #fff;
            padding: 14px;
        }
        .ds-sech{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 10px;
            margin-bottom: 12px;
        }
        .ds-sectitle{
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(1,52,91,.85);
        }
        .ds-hint{
            font-size: 11px;
            color: var(--pwa-muted);
        }

        .ds-label{
            display:block;
            font-size: 12px;
            color: #475569;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .ds-input, .ds-select{
            width: 100%;
            border: 1px solid rgba(2,6,23,.10);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
            outline: none;
            transition: .15s ease;
        }
        .ds-input:focus, .ds-select:focus{
            border-color: rgba(1,52,91,.35);
            box-shadow: 0 0 0 4px rgba(1,52,91,.10);
        }
        .ds-input[disabled]{
            background: rgba(2,6,23,.03);
            color: rgba(15,23,42,.70);
        }

        .ds-upload{
            border: 1px dashed rgba(2,6,23,.18);
            background: rgba(2,6,23,.02);
            border-radius: 14px;
            padding: 12px;
        }
        .ds-upload input[type="file"]{
            width: 100%;
        }

        .ds-filelist a{
            display:inline-flex;
            align-items:center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid rgba(2,6,23,.10);
            background: #fff;
            font-size: 12px;
            color: var(--ds-navy);
            font-weight: 800;
        }
        .ds-filelist a:hover{
            background: rgba(1,52,91,.06);
            border-color: rgba(1,52,91,.14);
        }

        .ds-actions{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(2,6,23,.06);
        }
        .ds-backlink{
            font-size: 13px;
            color: #64748b;
            font-weight: 700;
        }
        .ds-backlink:hover{ color:#0f172a; }

        .ds-save{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            color:#fff;
            font-weight: 900;
            font-size: 13px;
            background: var(--ds-navy);
            border: 1px solid rgba(1,52,91,.25);
            transition: .15s ease;
            white-space: nowrap;
        }
        .ds-save:hover{ background: var(--ds-pink); border-color: rgba(169,26,106,.35); }
    </style>
</head>

<body class="min-h-screen bg-slate-100">

@php
    $authUser = $user ?? Auth::user();
    $initials = strtoupper(
        mb_substr($authUser->first_name ?? '', 0, 1) . mb_substr($authUser->sur_name ?? '', 0, 1)
    );
@endphp

{{-- =========================
   MOBILE PWA SHELL (ONLY)
   ========================= --}}
<div class="ds-mobile-shell hidden">
    <header class="pwa-appbar fixed top-0 left-0 right-0 z-50">
        <div class="h-14 px-3 flex items-center justify-between">
            <div class="min-w-0">
                <div class="pwa-title truncate">
                    @yield('pwa-title', 'Personal Information')
                </div>
                <div class="pwa-subtitle truncate">
                    Review details & upload documents
                </div>
            </div>

            <button id="pwaUserBtn" type="button" class="pwa-avatar">
                {{ $initials }}
            </button>
        </div>

        {{-- User Sheet --}}
        <div id="pwaUserMenu" class="hidden">
            <div class="fixed inset-0">
                <div class="absolute inset-0 bg-black/30" data-close-pwa-menu></div>

                <div class="absolute bottom-0 left-0 right-0 pwa-sheet p-4">
                    <div class="pwa-sheet-handle"></div>

                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-10 w-10 rounded-full bg-slate-900 text-white text-xs font-extrabold flex items-center justify-center">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-extrabold text-slate-900 truncate">
                                {{ $authUser->first_name }} {{ $authUser->sur_name }}
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                {{ $authUser->email_address }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('portal.settings.profile') }}" class="pwa-sheet-item">
                        My Profile
                    </a>

                    <form method="POST" action="{{ route('portal.logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="pwa-sheet-item pwa-sheet-danger">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- Bottom Tab Bar --}}
    <nav class="pwa-tabbar fixed bottom-0 left-0 right-0">
        <div class="pwa-tabwrap">
            @php
                $path = request()->path();
                $isDashboard = request()->routeIs('portal.learner.dashboard');
                $isCourses = request()->routeIs('portal.learner.courses.*') || str_contains($path, 'courses');
                $isProfile = request()->routeIs('portal.settings.*') || str_contains($path, 'profile');
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

{{-- =========================
   DESKTOP HEADER (UNCHANGED)
   ========================= --}}
<header class="ds-desktop-header bg-white border-b border-slate-200">
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

{{-- =========================
   MAIN CONTENT
   ========================= --}}
<main class="pwa-main">
    <div class="min-h-screen py-6 md:py-8">
        <div class="max-w-4xl mx-auto px-0 md:px-4">
            <div class="ds-card p-4 md:p-6 space-y-6">

                <div>
                    <h1 class="text-lg md:text-xl font-extrabold text-slate-900">Personal Information</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Please review your details and upload required documents.
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('portal.profile.personal.update') }}"
                      enctype="multipart/form-data"
                      class="space-y-6">
                    @csrf

                    {{-- Basic Details --}}
                    <section class="ds-sec">
                        <div class="ds-sech">
                            <div class="ds-sectitle">Basic Details</div>
                            <div class="ds-hint">Read-only</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ds-label">First Name</label>
                                <input type="text" class="ds-input" value="{{ $user->first_name }}" disabled>
                            </div>
                            <div>
                                <label class="ds-label">Middle Name</label>
                                <input type="text" class="ds-input" value="{{ $user->middle_name }}" disabled>
                            </div>
                            <div>
                                <label class="ds-label">Surname</label>
                                <input type="text" class="ds-input" value="{{ $user->sur_name }}" disabled>
                            </div>
                            <div>
                                <label class="ds-label">Email Address</label>
                                <input type="text" class="ds-input" value="{{ $user->email_address }}" disabled>
                            </div>
                        </div>
                    </section>

                    {{-- Additional Details --}}
                    <section class="ds-sec">
                        <div class="ds-sech">
                            <div class="ds-sectitle">Additional Details</div>
                            <div class="ds-hint">Update your info</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ds-label">Contact Number</label>
                                <input type="text" name="contact" class="ds-input"
                                       value="{{ old('contact', $detail->contact ?? '') }}">
                            </div>

                            <div>
                                <label class="ds-label">Date of Birth</label>
                                <input type="date" name="dob" class="ds-input"
                                       value="{{ old('dob', optional($detail)->dob ? \Carbon\Carbon::parse($detail->dob)->format('Y-m-d') : '') }}">
                            </div>

                            <div>
                                <label class="ds-label">Gender</label>
                                <select name="gender" class="ds-select">
                                    <option value="">Select</option>
                                    <option value="male"   @selected(($detail->gender ?? '') === 'male')>Male</option>
                                    <option value="female" @selected(($detail->gender ?? '') === 'female')>Female</option>
                                    <option value="other"  @selected(($detail->gender ?? '') === 'other')>Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="ds-label">Country</label>
                                <input type="text" name="country" class="ds-input"
                                       value="{{ old('country', $detail->country ?? '') }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="ds-label">Address Line 1</label>
                                <input type="text" name="address_line_1" class="ds-input"
                                       value="{{ old('address_line_1', $detail->address_line_1 ?? '') }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="ds-label">Address Line 2</label>
                                <input type="text" name="address_line_2" class="ds-input"
                                       value="{{ old('address_line_2', $detail->address_line_2 ?? '') }}">
                            </div>

                            <div>
                                <label class="ds-label">City</label>
                                <input type="text" name="city" class="ds-input"
                                       value="{{ old('city', $detail->city ?? '') }}">
                            </div>

                            <div>
                                <label class="ds-label">State</label>
                                <input type="text" name="state" class="ds-input"
                                       value="{{ old('state', $detail->state ?? '') }}">
                            </div>

                            <div>
                                <label class="ds-label">Postcode / ZIP</label>
                                <input type="text" name="zip_code" class="ds-input"
                                       value="{{ old('zip_code', $detail->zip_code ?? '') }}">
                            </div>
                        </div>
                    </section>

                    {{-- Documents --}}
                    <section class="ds-sec space-y-5">
                        <div class="ds-sech">
                            <div class="ds-sectitle">Documents</div>
                            <div class="ds-hint">PDF / JPG / PNG</div>
                        </div>

                        {{-- Identity --}}
                        <div class="ds-upload">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-extrabold text-slate-900 text-sm">Identity Documents</div>
                                <div class="text-xs text-slate-500">Multiple allowed</div>
                            </div>
                            <input type="file" name="identity_documents[]" multiple class="block w-full text-sm text-slate-600">

                            @if($identityDocs->count())
                                <div class="ds-filelist mt-3 flex flex-wrap gap-2">
                                    @foreach($identityDocs as $doc)
                                        <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">
                                            {{ $doc->title ?? basename($doc->file_path) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Education --}}
                        <div class="ds-upload">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-extrabold text-slate-900 text-sm">Educational Documents</div>
                                <div class="text-xs text-slate-500">Multiple allowed</div>
                            </div>
                            <input type="file" name="education_documents[]" multiple class="block w-full text-sm text-slate-600">

                            @if($educationDocs->count())
                                <div class="ds-filelist mt-3 flex flex-wrap gap-2">
                                    @foreach($educationDocs as $doc)
                                        <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">
                                            {{ $doc->title ?? basename($doc->file_path) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Experience --}}
                        <div class="ds-upload">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-extrabold text-slate-900 text-sm">
                                    Experience Documents <span class="text-slate-500 text-xs font-bold">(optional)</span>
                                </div>
                                <div class="text-xs text-slate-500">Multiple allowed</div>
                            </div>
                            <input type="file" name="experience_documents[]" multiple class="block w-full text-sm text-slate-600">

                            @if($experienceDocs->count())
                                <div class="ds-filelist mt-3 flex flex-wrap gap-2">
                                    @foreach($experienceDocs as $doc)
                                        <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">
                                            {{ $doc->title ?? basename($doc->file_path) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Actions --}}
                    <div class="ds-actions">
                        <a href="{{ route('portal.learner.dashboard') }}" class="ds-backlink">
                            ← Back to dashboard
                        </a>

                        <button type="submit" class="ds-save">
                            Save Personal Information
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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

</body>
</html>
