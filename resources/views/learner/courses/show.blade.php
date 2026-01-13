@extends('layouts.learner')

@section('title', $course?->title ?? 'Course')

{{-- Mobile app bar title --}}
@section('pwa-title', Str::limit($course?->title ?? 'Course', 26))
@section('pwa-subtitle', 'Units • Notes • Assignments')

@section('content')
    <div class="min-h-screen bg-slate-50">
        <style>
/* ===========================================
   DIRECTSKILLS: COURSE SHOW (DESKTOP SAFE + MOBILE PWA)
   ✅ Desktop layout unchanged
   ✅ Sidebar only outer scroll
   ✅ Mobile: PWA-like readable UI
   =========================================== */

details>summary { list-style: none; }
details>summary::-webkit-details-marker { display: none; }

:root {
    --ds-pink: #a91a6a;
    --ds-navy: #01345b;

    --ds-bg: #f6f7fb;
    --ds-card: #ffffff;
    --ds-text: #0f172a;
    --ds-muted: #64748b;

    --ds-border: rgba(2, 6, 23, .10);
    --ds-border2: rgba(2, 6, 23, .08);

    --ds-shadow: 0 14px 35px rgba(2, 6, 23, 0.08);
}

/* Page background */
.bg-slate-50 { background: var(--ds-bg) !important; }

/* Base card */
.ds-card{
    background: var(--ds-card);
    border: 1px solid var(--ds-border2);
    border-radius: 18px;
    box-shadow: var(--ds-shadow);
}

/* Card header */
.ds-h{
    padding: 14px 16px;
    border-bottom: 1px solid var(--ds-border2);
    background: #fff;
    position: relative;
}
.ds-h::before{
    content: "";
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 5px;
    background: var(--ds-navy);
    border-top-left-radius: 18px;
    border-bottom-left-radius: 18px;
}

.ds-b{ padding: 16px 18px; }
.ds-soft{ box-shadow: inset 0 0 0 1px rgba(15,23,42,.06); }
.ds-border{ border-color: var(--ds-border2) !important; }
.ds-anchor{ scroll-margin-top: 110px; }

/* Shell */
.ds-shell{
    width: 100%;
    max-width: none !important;
    margin: 0 !important;
    padding: 18px 22px;
}

/* Desktop grid (UNCHANGED) */
.ds-layout{
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

/* Buttons */
.ds-backpill{
    border: 1px solid var(--ds-border2);
    background: #fff;
}

.ds-btn-primary{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 13px;
    color: #fff;
    background: var(--ds-navy);
    border: 1px solid rgba(1,52,91,.25);
    transition: .18s ease;
    white-space: nowrap;
}
.ds-btn-primary:hover{
    background: var(--ds-pink);
    border-color: rgba(169,26,106,.35);
}

.ds-action-outline{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding: 7px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 12px;
    color: var(--ds-navy);
    background: #fff;
    border: 1px solid rgba(1,52,91,.22);
    transition: .15s ease;
}
.ds-action-outline:hover{
    border-color: rgba(169,26,106,.30);
    color: var(--ds-pink);
}

.ds-action{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding: 7px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 12px;
    color: #fff;
    background: var(--ds-pink);
    border: 1px solid rgba(169,26,106,.25);
    transition: .15s ease;
}
.ds-action:hover{ background: #8f1459; }

/* Sidebar unit button */
.ds-unitbtn{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap: 12px;
    width:100%;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--ds-border);
    background:#fff;
    cursor:pointer;
    transition:.15s ease;
    text-align:left;
}
.ds-unitbtn:hover{ border-color: rgba(1,52,91,.22); }
.ds-unitbtn.is-active{
    border-color: rgba(169,26,106,.30);
    box-shadow: 0 10px 24px rgba(2,6,23,0.06);
}

.ds-meta{
    font-size: 11px;
    color: var(--ds-muted);
    margin-top: 4px;
    line-height: 1.15rem;
}

/* Small badges */
.ds-badge{
    display:inline-flex;
    align-items:center;
    gap: 6px;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11px;
    border: 1px solid rgba(1,52,91,.18);
    background: rgba(1,52,91,.06);
    color: var(--ds-navy);
    font-weight: 700;
}
.ds-badge-empty{
    border-color: rgba(148,163,184,.8);
    background: rgba(241,245,249,.8);
    color: rgba(71,85,105,1);
}

/* Chevrons */
.ds-chev,
.ds-right-chev{
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
    color: rgba(1,52,91,.70);
    transition: transform .18s ease;
}
.ds-unitbtn.is-open .ds-chev{ transform: rotate(180deg); }
details[open] .ds-right-chev{ transform: rotate(180deg); }

/* Right list */
.ds-list{
    border: 1px solid var(--ds-border2);
    border-radius: 14px;
    overflow: hidden;
    background:#fff;
}
.ds-row{
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border2);
}
.ds-row:last-child{ border-bottom: 0; }
.ds-row:hover{ background: rgba(1,52,91,.02); }

.ds-file::-webkit-file-upload-button{ display:none; }
.ds-file::file-selector-button{ display:none; }

.ds-title-navy{ color: var(--ds-navy) !important; }
.ds-lesson-ico{ background: rgba(1,52,91,.08) !important; color: var(--ds-navy) !important; }

.ds-assignment-card{ border-color: rgba(169,26,106,.18) !important; }
.ds-assignment-head{ background: rgba(169,26,106,.06) !important; border-bottom-color: rgba(169,26,106,.18) !important; }
.ds-assignment-ico{ background: rgba(169,26,106,.12) !important; color: var(--ds-pink) !important; }

.course-header-actions{
    margin-left: auto;
    display: flex;
    align-items: center;
}

/* ===============================
   DESKTOP BEHAVIOUR (KEEP)
   - sidebar subnav collapsed by default
   - only active unit content on right
   =============================== */
.ds-unitcard{ display:none; }
.ds-unitcard.is-active{ display:block; }

/* Unit wrapper */
.ds-unitwrap{
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background:#fff;
    overflow: hidden;
    transition: .15s ease;
    margin-bottom: 10px;
}
.ds-unitwrap.is-active{ border-color: rgba(169,26,106,.22); }
.ds-unitwrap.is-active.is-open {
    margin-bottom: 10px;
}
/* Unit header lives inside wrap */
.ds-unitwrap .ds-unitbtn{
    border: 0;
    border-radius: 0;
    background: transparent;
}

/* Subnav: hidden by default */
.ds-unitwrap .ds-subnav{
    display: none;
    padding: 10px;
    background: #fff;
    border-top: 1px solid rgba(2,6,23,.08);
}
.ds-unitwrap.is-open .ds-subnav{ display:block; }

/* Fix any long text overflow */
.ds-unitbtn p,
.ds-meta,
.ds-link,
.ds-link span{
    white-space: normal !important;
    word-break: break-word;
}

/* Link style (sidebar subnav links) */
.ds-link{
    display:flex;
    align-items:flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    text-decoration:none;
    color: var(--ds-text);
    font-size: 12px;
    line-height: 1.25rem;
    background: rgba(2,6,23,.02);
    border: 1px solid rgba(2,6,23,.08);
    transition: .12s ease;
    width: 100%;
}
.ds-link:hover{
    background: rgba(1,52,91,.04);
    border-color: rgba(1,52,91,.16);
}
.ds-link.is-active{
    background: rgba(169,26,106,.07);
    border-color: rgba(169,26,106,.18);
}

/* remove old left bar if any existed */
.ds-link::before{ display:none !important; }

/* Clamp titles for sidebar neatness */
.ds-link .font-semibold{
    color: var(--ds-navy);
    font-size: 12px;
    line-height: 1.15rem;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}
.ds-link .text-[11px]{ opacity: .85; }

/* Sub-section headings inside subnav */
.ds-subtitle{
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: rgba(1,52,91,.85);
    padding: 8px 10px;
    margin: 10px 0 8px;
    border: 1px solid rgba(2,6,23,.08);
    border-radius: 12px;
    background: rgba(1,52,91,.04);
}

/* Sub-card container for each section (Study Notes / Assignments) */
.ds-subtitle + .space-y-1{
    border: 1px solid rgba(2,6,23,.08);
    border-radius: 14px;
    background: #fff;
    padding: 8px;
    box-shadow: 0 10px 22px rgba(2,6,23,0.04);
    margin-bottom: 12px;
}

/* Tailwind space-y conflict fix */
.ds-sidebar-scroll.space-y-3 > * + *{ margin-top: 0 !important; }

/* ===============================
   ✅ DESKTOP: Sidebar scroll fix (ONLY outer scroll)
   =============================== */
@media (min-width:1024px){

    /* IMPORTANT: remove 920px minimum (it cuts units) */
    .ds-sidebar{
        position: sticky;
        top: 180px;
        height: calc(100vh - 192px);
        min-height: 520px;
    }

    /* card as column */
    .ds-sidebar .ds-card{
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* stop leaking outside */
    }

    /* header fixed */
    .ds-sidebar .ds-h{ flex: 0 0 auto; }

    /* only this scrolls */
    .ds-sidebar-scroll{
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        padding: 10px;
        gap: 12px;
    }

    /* NEVER create inner scroll */
    .ds-unitwrap .ds-subnav{
        max-height: none !important;
        overflow: visible !important;
    }
}

/* =========================
   MOBILE PWA VIEW (ONLY)
   - do NOT squeeze desktop UI
   ========================= */
@media (max-width:1023px){

    .ds-shell{ padding: 12px 14px; }

    /* hide 2-col desktop layout */
    .ds-layout{ display:none !important; }

    /* show your mobile container */
    .ds-mobile-units{ display:block !important; }

    /* hide desktop top row if you use appbar */
    .ds-toprow{ display:none !important; }

    /* Make mobile app-like typography */
    .ds-card{
        border-radius: 18px;
        box-shadow: 0 10px 22px rgba(2,6,23,0.06);
    }

    .ds-h{
        padding: 12px 14px;
    }

    /* Unit cards in mobile list (if you render them) */
    .ds-m-unit{
        border: 1px solid var(--ds-border2);
        border-radius: 18px;
        background:#fff;
        overflow:hidden;
        box-shadow: 0 10px 24px rgba(2,6,23,0.06);
    }

    .ds-m-summary{
        padding: 12px 14px;
        border-bottom: 1px solid var(--ds-border2);
        background:#fff;
    }

    .ds-m-title{
        font-size: 14px;
        font-weight: 600;
        color: var(--ds-navy);
        line-height: 1.25rem;
    }

    .ds-m-meta{
        font-size: 12px;
        color: var(--ds-muted);
        margin-top: 6px;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .ds-m-count{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        background: rgba(1,52,91,.06);
        border:1px solid rgba(1,52,91,.16);
        color: var(--ds-navy);
    }

    .ds-m-body{ padding: 14px; }

    .ds-m-sec{
        font-size: 12px;
        font-weight: 900;
        color: rgba(1,52,91,.85);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
}

@media (min-width:1024px){
    .ds-mobile-units{ display:none !important; }
}
</style>

        <main class="ds-shell space-y-4">

            {{-- Top row (DESKTOP only now) --}}
            <div class="ds-toprow flex items-center justify-between gap-3">
                <a href="{{ route('portal.learner.courses.all') }}"
                    class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
                    <span class="h-8 w-8 rounded-full ds-backpill ds-soft inline-flex items-center justify-center">←</span>
                    Back to my courses
                </a>

                <div class="course-header-actions">
                    <a href="{{ route('portal.learner.courses.all') }}" class="ds-btn-primary">
                        All Courses
                    </a>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Course header (both desktop + mobile) --}}
            <section class="ds-card">
                <div class="ds-b">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-semibold ds-title-navy leading-tight">
                                {{ $course?->title ?? 'Course' }}
                            </h1>

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white ds-soft"
                                    style="border:1px solid rgba(1,52,91,.18); color: var(--ds-navy); font-weight:700;">
                                    DirectSkills Student Number (SID): <b>DS{{ $user->id }}</b>
                                </span>

                                @if ((int) $enrolment->status_id === 2)
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full"
                                        style="background: rgba(1,52,91,.08); border:1px solid rgba(1,52,91,.18); color: var(--ds-navy); font-weight:700;">
                                        Approved
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full"
                                        style="background: rgba(169,26,106,.10); border:1px solid rgba(169,26,106,.18); color: var(--ds-pink); font-weight:700;">
                                        Pending
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Desktop action (keep) --}}
                        <div class="course-header-actions hidden lg:flex">
                            <a href="{{ route('portal.learner.courses.all') }}" class="ds-btn-primary">
                                All Courses
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            @if ($modules->count())

                {{-- =========================
                MOBILE: Unit-first accordion list
                (true PWA flow, no sidebar selection)
               ========================= --}}
                <section class="ds-mobile-units hidden space-y-3">
                    @foreach ($modules as $index => $module)
                        @php
                            $isEmpty = $module->lessons->count() + $module->assignments->count() === 0;
                        @endphp

                        <div class="ds-m-unit">
                            <details>
                                <summary class="ds-m-summary cursor-pointer select-none">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="ds-m-title">
                                                Unit {{ $index + 1 }}: {{ $module->title ?? 'Unit ' . ($index + 1) }}
                                            </div>
                                            <div class="ds-m-meta">
                                                <span class="ds-m-count">📄 {{ $module->lessons->count() }} Notes</span>
                                                <span class="ds-m-count">📝 {{ $module->assignments->count() }}
                                                    Assignments</span>
                                                @if ($isEmpty)
                                                    <span class="ds-m-count"
                                                        style="background: rgba(241,245,249,.9); border-color: rgba(148,163,184,.6); color: rgba(71,85,105,1);">
                                                        Empty
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <svg class="ds-right-chev mt-1" viewBox="0 0 20 20" fill="currentColor"
                                            aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </summary>

                                <div class="ds-m-body space-y-5">
                                    @if ($isEmpty)
                                        <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                                            This unit has no study notes or assignments yet.
                                        </div>
                                    @endif

                                    {{-- Study Notes (inside unit) --}}
                                    @if ($module->lessons->count())
                                        <div>
                                            <div class="ds-m-sec">Study Notes</div>

                                            <div class="ds-list">
                                                @foreach ($module->lessons as $lesson)
                                                    @php
                                                        $hasContent = !blank($lesson->content);
                                                        $file = $lesson->file_path;
                                                        $isUrl =
                                                            $file &&
                                                            \Illuminate\Support\Str::startsWith($file, [
                                                                'http://',
                                                                'https://',
                                                            ]);
                                                        $hasSpItem = !blank($lesson->sharepoint_item_id ?? null);
                                                        $hasSpUrl = !blank($lesson->sharepoint_url ?? null);
                                                    @endphp

                                                    <div class="ds-row">
                                                        <div class="flex items-start gap-3">
                                                            <div
                                                                class="h-9 w-9 rounded-xl flex items-center justify-center font-bold ds-soft ds-lesson-ico">
                                                                📄</div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="text-sm font-semibold ds-title-navy">
                                                                    {{ $lesson->title }}
                                                                </div>

                                                                <div class="mt-2 flex flex-wrap gap-2">
                                                                    <a href="{{ route('portal.learner.lessons.show', $lesson->id) }}"
                                                                        class="ds-action">
                                                                        Open
                                                                    </a>

                                                                    @if (!blank($file))
                                                                        @if ($isUrl)
                                                                            <a href="{{ $file }}" target="_blank"
                                                                                class="ds-action-outline">File</a>
                                                                        @elseif($hasSpItem)
                                                                            <a href="{{ route('portal.learner.lesson.file.inline', $lesson->id) }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">Online</a>
                                                                            <a href="{{ route('portal.learner.lesson.file.download', $lesson->id) }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">Download</a>
                                                                        @elseif($hasSpUrl)
                                                                            <a href="{{ $lesson->sharepoint_url }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">File</a>
                                                                        @endif
                                                                    @endif

                                                                    @if (!blank($lesson->video_url))
                                                                        <a href="{{ $lesson->video_url }}" target="_blank"
                                                                            class="ds-action-outline">
                                                                            Lesson
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Assignments (inside unit) --}}
                                    @if ($module->assignments->count())
                                        <div>
                                            <div class="ds-m-sec">Assignments</div>

                                            <div class="flex flex-col gap-3">
                                                @foreach ($module->assignments as $assignment)
                                                    @php
                                                        $submission = $assignment->submissions->first();
                                                        $brief = $assignment->files->first();
                                                    @endphp

                                                    <div
                                                        class="bg-white rounded-2xl border overflow-hidden ds-assignment-card">
                                                        <div
                                                            class="px-4 py-3 border-b flex items-center justify-between gap-3 ds-assignment-head">
                                                            <div class="flex items-start gap-3 min-w-0">
                                                                <div
                                                                    class="h-9 w-9 rounded-xl flex items-center justify-center font-bold ds-soft ds-assignment-ico">
                                                                    📝</div>
                                                                <div class="min-w-0">
                                                                    <div class="text-sm font-semibold ds-title-navy">
                                                                        {{ $assignment->title }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @if ($brief)
                                                                <a href="{{ $brief->file_path }}" target="_blank"
                                                                    class="ds-action-outline">
                                                                    Brief
                                                                </a>
                                                            @endif
                                                        </div>

                                                        <div class="p-4">
                                                            <div class="rounded-2xl bg-white border ds-border p-4">
                                                                @if ($submission)
                                                                    <div class="flex items-center justify-between gap-3">
                                                                        <a href="{{ route('portal.learner.submission.view', $submission->id) }}"
                                                                            target="_blank" class="ds-action">
                                                                            Download
                                                                        </a>
                                                                        <span
                                                                            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold"
                                                                            style="background: rgba(1,52,91,.08); color: var(--ds-navy); border:1px solid rgba(1,52,91,.16);">
                                                                            {{ ucfirst($submission->status ?? 'submitted') }}
                                                                        </span>
                                                                    </div>
                                                                @else
                                                                    <form
                                                                        action="{{ route('portal.learner.assignment.submit', $assignment->id) }}"
                                                                        method="POST" enctype="multipart/form-data"
                                                                        class="space-y-3">
                                                                        @csrf

                                                                        <div>
                                                                            <label
                                                                                class="block text-xs font-semibold ds-title-navy mb-1">Upload
                                                                                your file</label>
                                                                            <label
                                                                                class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white border ds-border cursor-pointer hover:bg-slate-50">
                                                                                <span
                                                                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold"
                                                                                    style="background: var(--ds-pink); color:#fff;">
                                                                                    Choose file
                                                                                </span>
                                                                                <span
                                                                                    class="text-xs text-slate-600 truncate"
                                                                                    id="file-name-m-{{ $assignment->id }}">No
                                                                                    file selected</span>
                                                                                <input type="file" name="submission_file"
                                                                                    required class="hidden ds-file"
                                                                                    onchange="document.getElementById('file-name-m-{{ $assignment->id }}').innerText = this.files?.[0]?.name ?? 'No file selected';">
                                                                            </label>
                                                                            <p class="text-[11px] text-slate-500 mt-1">
                                                                                Word/PDF etc. Max 20MB.</p>
                                                                        </div>

                                                                        <button type="submit"
                                                                            class="ds-btn-primary w-full">
                                                                            Submit
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </details>
                        </div>
                    @endforeach
                </section>

                {{-- =========================
                DESKTOP (UNCHANGED)
               ========================= --}}
                <div class="ds-layout">

                    {{-- Left Sidebar --}}
                    <aside class="ds-sidebar">
                        <div class="ds-card h-full overflow-hidden">
                            <div class="ds-h">
                                <p class="text-sm font-semibold ds-title-navy">Course Navigation</p>
                                <p class="text-[11px] text-slate-600 mt-0.5">Click on the Units to access the content on
                                    the right.</p>
                            </div>

                            <div class="ds-sidebar-scroll ds-nav space-y-3">
                                @foreach ($modules as $i => $m)
                                    @php
                                        $isEmpty = $m->lessons->count() + $m->assignments->count() === 0;
                                    @endphp

                                    <div class="ds-unitwrap ds-unitwrap-{{ $m->id }}">
                                        <button type="button" class="ds-unitbtn ds-unitbtn-{{ $m->id }}"
                                            data-open-unit="{{ $m->id }}">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold ds-title-navy">
                                                    Unit {{ $i + 1 }}: {{ $m->title ?? 'Unit ' . ($i + 1) }}
                                                </p>
                                                <p class="ds-meta">
                                                    {{ $m->lessons->count() }} Study Notes •
                                                    {{ $m->assignments->count() }} Assignments
                                                </p>

                                                @if ($isEmpty)
                                                    <div class="mt-2">
                                                        <span class="ds-badge ds-badge-empty">Empty unit</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <svg class="ds-right-chev" viewBox="0 0 20 20" fill="currentColor"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div class="ds-subnav ds-subnav-{{ $m->id }}">
                                            @if ($m->lessons->count())
                                                <div class="ds-subtitle">Study Notes</div>
                                                <div class="space-y-1">
                                                    @foreach ($m->lessons as $lesson)
                                                        <a href="#lesson-{{ $lesson->id }}" class="ds-link">
                                                            <span class="min-w-0">
                                                                <span
                                                                    class="font-semibold block">{{ $lesson->title }}</span>
                                                                <span
                                                                    class="text-[11px] text-slate-600 block">Lesson</span>
                                                            </span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($m->assignments->count())
                                                <div class="ds-subtitle">Assignments</div>
                                                <div class="space-y-1">
                                                    @foreach ($m->assignments as $assignment)
                                                        <a href="#assignment-{{ $assignment->id }}" class="ds-link">
                                                            <span class="min-w-0">
                                                                <span
                                                                    class="font-semibold block">{{ $assignment->title }}</span>
                                                                <span class="text-[11px] text-slate-600 block">Upload &
                                                                    status</span>
                                                            </span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </aside>

                    {{-- Right Content --}}
                    <section class="min-w-0 space-y-4 ds-content">
                        @foreach ($modules as $index => $module)
                            @php
                                $unitAnchor = "unit-{$module->id}";
                                $isEmpty = $module->lessons->count() + $module->assignments->count() === 0;
                            @endphp

                            <div id="{{ $unitAnchor }}" class="ds-anchor ds-card overflow-hidden ds-unitcard"
                                data-unit-card="{{ $module->id }}">
                                <details @if ($index === 0) open @endif>
                                    <summary class="ds-h cursor-pointer select-none">
                                        <div class="flex items-start sm:items-center justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold"
                                                        style="background: rgba(1,52,91,.08); color: var(--ds-navy); border:1px solid rgba(1,52,91,.16);">
                                                        Unit {{ $index + 1 }}
                                                    </span>

                                                    <h2 class="text-base sm:text-lg font-semibold ds-title-navy">
                                                        {{ $module->title ?? 'Unit ' . ($index + 1) }}
                                                    </h2>

                                                    @if ($isEmpty)
                                                        <span class="ds-badge ds-badge-empty">Empty</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="ds-badge">📘 {{ $module->lessons->count() }}</span>
                                                <span class="ds-badge">🧾 {{ $module->assignments->count() }}</span>

                                                <svg class="ds-right-chev" viewBox="0 0 20 20" fill="currentColor"
                                                    aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </summary>

                                    <div class="ds-b space-y-5">
                                        @if ($isEmpty)
                                            <div
                                                class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                                                This unit has no study notes or assignments yet.
                                            </div>
                                        @endif

                                        {{-- Lessons --}}
                                        @if ($module->lessons->count())
                                            <div>
                                                <h3 class="text-sm font-semibold ds-title-navy mb-2">Study Notes</h3>

                                                <div class="ds-list">
                                                    @foreach ($module->lessons as $lesson)
                                                        @php
                                                            $hasContent = !blank($lesson->content);
                                                            $file = $lesson->file_path;
                                                            $isUrl =
                                                                $file &&
                                                                \Illuminate\Support\Str::startsWith($file, [
                                                                    'http://',
                                                                    'https://',
                                                                ]);
                                                            $hasSpItem = !blank($lesson->sharepoint_item_id ?? null);
                                                            $hasSpUrl = !blank($lesson->sharepoint_url ?? null);
                                                        @endphp

                                                        <div id="lesson-{{ $lesson->id }}" class="ds-anchor ds-row">
                                                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                                                    <div
                                                                        class="h-9 w-9 rounded-xl flex items-center justify-center font-bold ds-soft ds-lesson-ico">
                                                                        📄
                                                                    </div>

                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2 flex-wrap">
                                                                            <a href="{{ route('portal.learner.lessons.show', $lesson->id) }}"
                                                                                class="text-sm font-semibold ds-title-navy hover:underline hover:text-[var(--ds-pink)]">
                                                                                {{ $lesson->title }}
                                                                            </a>

                                                                            <span
                                                                                class="text-[11px] px-2 py-0.5 rounded-full"
                                                                                style="background: rgba(1,52,91,.08); color: var(--ds-navy); border:1px solid rgba(1,52,91,.16);">
                                                                                Lesson
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div
                                                                    class="flex flex-wrap gap-2 justify-start sm:justify-end">
                                                                    @if ($hasContent)
                                                                        <a href="{{ route('portal.learner.lessons.show', $lesson->id) }}"
                                                                            class="ds-action">Open</a>
                                                                    @endif

                                                                    @if (!blank($file))
                                                                        @if ($isUrl)
                                                                            <a href="{{ $file }}" target="_blank"
                                                                                class="ds-action-outline">File</a>
                                                                        @elseif($hasSpItem)
                                                                            <a href="{{ route('portal.learner.lesson.file.inline', $lesson->id) }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">Online</a>
                                                                            <a href="{{ route('portal.learner.lesson.file.download', $lesson->id) }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">Download</a>
                                                                        @elseif($hasSpUrl)
                                                                            <a href="{{ $lesson->sharepoint_url }}"
                                                                                target="_blank"
                                                                                class="ds-action-outline">File</a>
                                                                        @endif
                                                                    @endif

                                                                    @if (!blank($lesson->video_url))
                                                                        <a href="{{ $lesson->video_url }}"
                                                                            target="_blank"
                                                                            class="ds-action-outline">Lesson</a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Assignments --}}
                                        @if ($module->assignments->count())
                                            <div>
                                                <h3 class="text-sm font-semibold ds-title-navy mb-2">Assignments</h3>

                                                <div class="flex flex-col gap-3">
                                                    @foreach ($module->assignments as $assignment)
                                                        @php
                                                            $submission = $assignment->submissions->first();
                                                            $brief = $assignment->files->first();
                                                        @endphp

                                                        <div id="assignment-{{ $assignment->id }}"
                                                            class="bg-white rounded-2xl border overflow-hidden ds-assignment-card">
                                                            <div
                                                                class="px-4 py-3 border-b flex items-center justify-between gap-3 ds-assignment-head">
                                                                <div class="flex items-start gap-3 min-w-0">
                                                                    <div
                                                                        class="h-9 w-9 rounded-xl flex items-center justify-center font-bold ds-soft ds-assignment-ico">
                                                                        📝</div>
                                                                    @if ($brief)
                                                                        <a href="{{ $brief->file_path }}" target="_blank"
                                                                            class="text-sm font-semibold ds-title-navy hover:underline hover:text-[var(--ds-pink)]">
                                                                            {{ $assignment->title }}
                                                                        </a>
                                                                    @else
                                                                        <span
                                                                            class="text-sm font-semibold ds-title-navy">{{ $assignment->title }}</span>
                                                                    @endif
                                                                </div>

                                                                @if ($brief)
                                                                    <a href="{{ $brief->file_path }}" target="_blank"
                                                                        class="ds-action-outline">Open brief</a>
                                                                @endif
                                                            </div>

                                                            <div class="p-4">
                                                                <div class="rounded-2xl bg-white border ds-border p-4">
                                                                    @if ($submission)
                                                                        <div
                                                                            class="flex items-center justify-between gap-3">
                                                                            <a href="{{ route('portal.learner.submission.view', $submission->id) }}"
                                                                                target="_blank" class="ds-action">
                                                                                Download submission
                                                                            </a>
                                                                            <span
                                                                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold"
                                                                                style="background: rgba(1,52,91,.08); color: var(--ds-navy); border:1px solid rgba(1,52,91,.16);">
                                                                                {{ ucfirst($submission->status ?? 'submitted') }}
                                                                            </span>
                                                                        </div>
                                                                    @else
                                                                        <form
                                                                            action="{{ route('portal.learner.assignment.submit', $assignment->id) }}"
                                                                            method="POST" enctype="multipart/form-data"
                                                                            class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                                                            @csrf

                                                                            <div class="sm:col-span-8">
                                                                                <label
                                                                                    class="block text-xs font-semibold ds-title-navy mb-1">Upload
                                                                                    your file</label>
                                                                                <label
                                                                                    class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white border ds-border cursor-pointer hover:bg-slate-50">
                                                                                    <span
                                                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold"
                                                                                        style="background: var(--ds-pink); color:#fff;">
                                                                                        Choose file
                                                                                    </span>
                                                                                    <span
                                                                                        class="text-xs text-slate-600 truncate"
                                                                                        id="file-name-{{ $assignment->id }}">No
                                                                                        file selected</span>
                                                                                    <input type="file"
                                                                                        name="submission_file" required
                                                                                        class="hidden ds-file"
                                                                                        onchange="document.getElementById('file-name-{{ $assignment->id }}').innerText = this.files?.[0]?.name ?? 'No file selected';">
                                                                                </label>
                                                                                <p class="text-[11px] text-slate-500 mt-1">
                                                                                    Word/PDF etc. Max 20MB.</p>
                                                                            </div>

                                                                            <div class="sm:col-span-4">
                                                                                <button type="submit"
                                                                                    class="ds-btn-primary w-full">Submit</button>
                                                                            </div>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </section>
                </div>
            @else
                <section class="ds-card p-6 text-sm text-slate-600">
                    Course content will be available soon.
                </section>
            @endif
        </main>

        {{-- Desktop JS (UNCHANGED): sidebar selection logic for right panel --}}
        <script>
            (function() {
                const unitButtons = Array.from(document.querySelectorAll('[data-open-unit]'));
                const unitCards = Array.from(document.querySelectorAll('[data-unit-card]'));

                function openUnit(unitId, shouldScroll = true) {
                    unitButtons.forEach(btn => {
                        const isThis = btn.getAttribute('data-open-unit') === String(unitId);
                        btn.classList.toggle('is-active', isThis);
                        const wrap = btn.closest('.ds-unitwrap');
                        if (wrap) {
                            wrap.classList.toggle('is-active', isThis);
                            wrap.classList.toggle('is-open', isThis);
                        }
                    });

                    unitCards.forEach(card => {
                        const id = card.getAttribute('data-unit-card');
                        const det = card.querySelector('details');
                        const isSelected = (id === String(unitId));
                        card.classList.toggle('is-active', isSelected);
                        if (det) det.open = isSelected;
                    });

                    if (shouldScroll) {
                        const anchor = document.getElementById('unit-' + unitId);
                        anchor?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }

                const first = unitButtons[0];
                if (first) {
                    openUnit(first.getAttribute('data-open-unit'), false);
                }

                unitButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const unitId = btn.getAttribute('data-open-unit');
                        openUnit(unitId, true);
                    });
                });

                const navLinks = Array.from(document.querySelectorAll('.ds-nav a[href^="#"]'));

                function setActive(href) {
                    navLinks.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === href));
                }

                navLinks.forEach(a => {
                    a.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        const el = document.querySelector(href);
                        if (!el) return;

                        e.preventDefault();
                        el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        history.pushState(null, '', href);
                        setActive(href);

                        const unitWrap = el.closest('[data-unit-card]');
                        if (unitWrap) {
                            const unitId = unitWrap.getAttribute('data-unit-card');
                            openUnit(unitId, false);
                        }
                    });
                });
            })();
        </script>
    </div>
@endsection
