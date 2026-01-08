@extends('layouts.learner')

@section('title', 'Learner Dashboard')

@section('content')
<div class="min-h-screen ds-page">
    <style>
        :root{
            --ds-pink: #a91a6a;
            --ds-navy: #01345b;

            --ds-border: rgba(15,23,42,.10);
            --ds-border2: rgba(15,23,42,.08);
            --ds-shadow: 0 10px 26px rgba(15,23,42,.06);

            --ds-navy-soft: rgba(1,52,91,.07);
            --ds-pink-soft: rgba(169,26,106,.08);
        }

        /* Page bg (NO gradient) */
        .ds-page{
            background: #f8fafc;
        }

        /* Shell same as LMS */
        .ds-shell{
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 18px 14px;
        }

        /* Card system */
        .ds-card{
            background:#fff;
            border:1px solid var(--ds-border2);
            border-radius:16px;
            box-shadow: var(--ds-shadow);
        }
        .ds-h{
            padding: 14px 16px;
            border-bottom:1px solid var(--ds-border2);
            background: rgba(1,52,91,.04); /* subtle brand tint */
        }
        .ds-b{ padding:14px 16px; }

        .ds-title{ color: var(--ds-navy); }
        .ds-muted{ color: rgba(71,85,105,1); }

        /* Pills / badges */
        .ds-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding: 6px 12px;
            border-radius:999px;
            font-size: 12px;
            border:1px solid var(--ds-border2);
            background:#fff;
            color: var(--ds-navy);
            box-shadow: inset 0 0 0 1px rgba(15,23,42,.04);
        }

        .ds-badge{
            display:inline-flex;
            align-items:center;
            padding: 5px 10px;
            border-radius:999px;
            font-size: 11px;
            font-weight: 600;
            border:1px solid var(--ds-border2);
            background:#fff;
            color: var(--ds-navy);
        }
        .ds-badge-approved{
            background: rgba(1,52,91,.08);
            border-color: rgba(1,52,91,.18);
            color: var(--ds-navy);
        }
        .ds-badge-pending{
            background: rgba(169,26,106,.10);
            border-color: rgba(169,26,106,.18);
            color: var(--ds-pink);
        }
        .ds-badge-denied{
            background: rgba(225,29,72,.08);
            border-color: rgba(225,29,72,.18);
            color: rgb(190,18,60);
        }
        .ds-badge-wait{
            background: rgba(100,116,139,.10);
            border-color: rgba(100,116,139,.18);
            color: rgba(51,65,85,1);
        }

        /* Buttons */
        .ds-btn-primary{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color:#fff;
            background: var(--ds-pink);
            border: 1px solid rgba(169,26,106,.30);
            box-shadow: 0 10px 20px rgba(169,26,106,.22);
            transition: .15s ease;
            white-space: nowrap;
        }
        .ds-btn-primary:hover{
            filter: brightness(.98);
            box-shadow: 0 14px 26px rgba(169,26,106,.28);
        }

        .ds-btn-outline{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 12px;
            color: var(--ds-navy);
            background: rgba(1,52,91,.04);
            border: 1px solid rgba(1,52,91,.18);
            transition: .15s ease;
            white-space: nowrap;
        }
        .ds-btn-outline:hover{
            background: rgba(1,52,91,.08);
        }

        /* Links */
        .ds-link{
            color: var(--ds-navy);
            font-weight: 600;
            text-decoration: none;
        }
        .ds-link:hover{
            color: var(--ds-pink);
            text-decoration: underline;
        }

        /* Section icons */
        .ds-icon{
            height: 34px;
            width: 34px;
            border-radius: 999px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-weight: 800;
            border:1px solid var(--ds-border2);
            background: rgba(1,52,91,.06);
            color: var(--ds-navy);
        }
        .ds-icon-pink{
            background: rgba(169,26,106,.08);
            color: var(--ds-pink);
            border-color: rgba(169,26,106,.16);
        }

        /* Row separators */
        .ds-divider > * + *{
            border-top: 1px solid rgba(15,23,42,.06);
        }

        /* Microsoft 365 strip */
        .ds-m365-pill{
            height: 44px;
            width: 44px;
            border-radius: 12px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#fff;
            border:1px solid var(--ds-border2);
            box-shadow: inset 0 0 0 1px rgba(15,23,42,.03);
        }

        .ds-m365-pill img{
            height: 24px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }

        /* Responsive */
        @media (min-width: 1024px){
            .ds-shell{ padding: 20px 0; }
        }
    </style>

    <div class="ds-shell space-y-4">

        {{-- TOP WELCOME --}}
        <section class="ds-card">
            <div class="ds-b">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-wide uppercase mb-1"
                           style="color: var(--ds-pink);">
                            Welcome
                        </p>
                        <h1 class="text-xl font-bold ds-title leading-tight">
                            {{ $user->first_name }} {{ $user->sur_name }}
                        </h1>
                        <p class="text-[13px] ds-muted mt-1">
                            Check your profile, complete required forms, and access your enrolled courses.
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="ds-pill">
                                DirectSkills Student Number (SID): <b>DS{{ $user->id }}</b>
                            </span>
                            <span class="ds-pill" style="max-width: 100%; overflow:hidden;">
                                <span class="truncate">Email: <b>{{ $user->email_address }}</b></span>
                            </span>
                        </div>
                    </div>

                    {{-- Right side CTA (optional) --}}
                    {{-- <div class="flex items-center gap-2 sm:justify-end">
                        <a href="{{ route('portal.learner.courses.all') }}" class="ds-btn-primary">
                            View Courses
                        </a>
                    </div> --}}
                </div>
            </div>
        </section>

        {{-- ENROLMENT DENIED NOTICE --}}
        @if (!empty($isDenied) && $isDenied)
            <section class="ds-card" style="border-color: rgba(225,29,72,.18);">
                <div class="ds-b">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold mb-1" style="color: rgb(190,18,60);">
                                Enrolment Denied
                            </p>
                            <p class="text-[13px]" style="color: rgba(190,18,60,.9);">
                                Your enrolment has been denied. Please refill your information correctly and submit again.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <a href="{{ route('portal.profile.personal') }}" class="ds-btn-outline" style="border-color: rgba(225,29,72,.22); color: rgb(190,18,60); background: rgba(225,29,72,.06);">
                                Refill Personal
                            </a>
                            <a href="{{ route('portal.profile.rpl') }}" class="ds-btn-outline" style="border-color: rgba(225,29,72,.22); color: rgb(190,18,60); background: rgba(225,29,72,.06);">
                                Refill RPL
                            </a>
                            <a href="{{ route('portal.profile.disability') }}" class="ds-btn-outline" style="border-color: rgba(225,29,72,.22); color: rgb(190,18,60); background: rgba(225,29,72,.06);">
                                Refill Disability
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- MAIN GRID --}}
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.7fr)]">

            {{-- LEFT --}}
            <div class="space-y-4">

                {{-- MY PROFILE --}}
                <section class="ds-card">
                    <div class="ds-h">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-bold ds-title flex items-center gap-2">
                                <span class="ds-icon">MP</span>
                                My Profile
                            </h2>

                            <a href="{{ route('portal.settings.profile') }}" class="ds-btn-outline">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M16.862 3.487l2.651 2.651M7 14l6.879-6.879a2 2 0 012.829 0l1.758 1.758a2 2 0 010 2.829L11.587 19H7v-5z" />
                                </svg>
                                Edit
                            </a>
                        </div>
                    </div>

                    <div class="ds-b">
                        <dl class="space-y-2 text-[13px] text-slate-700">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Name:</dt>
                                <dd class="font-semibold text-right ds-title">
                                    {{ $user->first_name }} {{ $user->sur_name }}
                                </dd>
                            </div>

                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Email:</dt>
                                <dd class="font-semibold text-right truncate max-w-[260px] ds-title">
                                    {{ $user->email_address }}
                                </dd>
                            </div>

                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">DirectSkills Student Number (SID):</dt>
                                <dd class="font-semibold text-right ds-title">DS{{ $user->id }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                {{-- REQUIREMENTS --}}
                <section class="ds-card">
                    <div class="ds-h">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-bold ds-title flex items-center gap-2">
                                <span class="ds-icon ds-icon-pink">✔</span>
                                Requirements
                            </h2>
                            <p class="text-[11px] ds-muted mt-0.5">
                                Complete these steps to start your learning.
                            </p>
                        </div>
                    </div>

                    <div class="ds-b">
                        <div class="space-y-3 text-[13px]">

                            {{-- Personal --}}
                            <div class="flex items-start justify-between gap-3 py-2">
                                <div class="min-w-0">
                                    <p class="font-semibold ds-title">Personal Information</p>
                                    <p class="text-[11px] ds-muted">Basic details and contact information.</p>
                                </div>

                                <div class="text-right shrink-0">
                                    @if ($personalCompleted)
                                        <span class="ds-badge ds-badge-approved">Completed</span>
                                    @else
                                        <span class="ds-badge ds-badge-pending">Needs to fill</span>
                                        <a href="{{ route('portal.profile.personal') }}" class="block text-[11px] mt-1 ds-link">
                                            Fill Personal Information
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div style="border-top:1px solid rgba(15,23,42,.06)"></div>

                            {{-- RPL --}}
                            <div class="flex items-start justify-between gap-3 py-2">
                                <div class="min-w-0">
                                    <p class="font-semibold ds-title">RPL Information</p>
                                    <p class="text-[11px] ds-muted">Recognition of prior learning and experience.</p>
                                </div>

                                <div class="text-right shrink-0">
                                    @if ($rplCompleted)
                                        <span class="ds-badge ds-badge-approved">Completed</span>
                                    @else
                                        <span class="ds-badge ds-badge-pending">Needs to fill</span>
                                        <a href="{{ route('portal.profile.rpl') }}" class="block text-[11px] mt-1 ds-link">
                                            Fill RPL Information
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div style="border-top:1px solid rgba(15,23,42,.06)"></div>

                            {{-- Disability --}}
                            <div class="flex items-start justify-between gap-3 py-2">
                                <div class="min-w-0">
                                    <p class="font-semibold ds-title">Disability Information</p>
                                    <p class="text-[11px] ds-muted">Support needs and reasonable adjustments.</p>
                                </div>

                                <div class="text-right shrink-0">
                                    @if ($disabilityCompleted)
                                        <span class="ds-badge ds-badge-approved">Completed</span>
                                    @else
                                        <span class="ds-badge ds-badge-pending">Needs to fill</span>
                                        <a href="{{ route('portal.profile.disability') }}" class="block text-[11px] mt-1 ds-link">
                                            Fill Disability Information
                                        </a>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </section>
            </div>

            {{-- RIGHT: COURSES --}}
            <section class="ds-card">
                <div class="ds-h">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold ds-title flex items-center gap-2">
                            <span class="ds-icon">📚</span>
                            My Courses
                        </h2>

                        @if ($enrolments->count())
                            <span class="text-[11px] ds-muted">
                                Showing {{ min(5, $enrolments->count()) }} of {{ $enrolments->count() }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="ds-b">
                    @php
                        $limitedEnrolments = $enrolments->take(5);
                    @endphp

                    @if ($limitedEnrolments->count())
                        <div class="ds-divider text-[13px]">
                            @foreach ($limitedEnrolments as $enrolment)
                                @php
                                    $course   = $enrolment->course ?? null;
                                    $approved = (int)$enrolment->status_id === 2;
                                    $denied   = (int)$enrolment->status_id === 3;
                                @endphp

                                <div class="py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold ds-title">
                                            @if ($approved && $course)
                                                <a href="{{ route('portal.learner.course.show', $enrolment->id) }}" class="ds-link">
                                                    {{ $course->title }}
                                                </a>
                                            @else
                                                {{ $course?->title ?? 'Course #' . $enrolment->id }}
                                            @endif
                                        </p>
                                        <p class="text-[11px] ds-muted mt-0.5">
                                            DirectSkills Student Number (SID): <b>DS{{ $user->id }}</b>
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right flex flex-col items-end gap-1">
                                        @if ($approved)
                                            <span class="ds-badge ds-badge-approved">Approved</span>
                                            <a href="{{ route('portal.learner.course.show', $enrolment->id) }}" class="text-[11px] ds-link">
                                                Go to course
                                            </a>
                                        @elseif ($denied)
                                            <span class="ds-badge ds-badge-denied">Denied - Refill Required</span>
                                            <a href="{{ route('portal.settings.profile') }}" class="text-[11px]" style="color: rgb(190,18,60); font-weight:600; text-decoration: underline;">
                                                Update your details
                                            </a>
                                        @else
                                            <span class="ds-badge ds-badge-wait">Awaiting approval</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- View all --}}
                        @if ($enrolments->count() > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('portal.learner.courses.all') }}" class="ds-link text-[12px]">
                                    View all courses →
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="text-[13px] ds-muted">
                            You are not enrolled in any course yet.
                        </p>
                    @endif
                </div>
            </section>

        </div>
        {{-- MICROSOFT 365 ACCESS --}}
        <section class="ds-card">
            <div class="ds-b">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">

                    {{-- Left info --}}
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-wide uppercase mb-1"
                        style="color: var(--ds-navy);">
                            Microsoft 365 Access
                        </p>

                        <h2 class="text-base font-bold ds-title">
                            Your Microsoft 365 Web License
                        </h2>

                        <p class="text-[13px] ds-muted mt-1 max-w-xl">
                            Access official Microsoft tools to communicate with tutors,
                            collaborate on coursework, and manage learning content.
                        </p>

                        <div class="mt-3 text-[11px] ds-muted">
                            Login using your learner email to access Microsoft 365 web apps and Teams.
                        </div>
                    </div>

                    {{-- Right icons --}}
                    <div class="flex flex-wrap items-center gap-3 shrink-0" style="margin-left: auto;">

                        <div class="ds-m365-pill">
                            <a href="https://www.office.com" target="_blank" title="Microsoft 365">
                                <img src="https://www.microsoft.com/favicon.ico" alt="Microsoft 365">
                            </a>
                        </div>

                        <div class="ds-m365-pill">
                            <a href="https://outlook.office.com" target="_blank" title="Outlook">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Microsoft_Outlook_Icon_%282025%E2%80%93present%29.svg/1200px-Microsoft_Outlook_Icon_%282025%E2%80%93present%29.svg.png" alt="Outlook">
                            </a>
                        </div>

                        <div class="ds-m365-pill">
                            <a href="https://www.office.com/launch/excel" target="_blank" title="Excel">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/60/Microsoft_Office_Excel_%282025%E2%80%93present%29.svg/1166px-Microsoft_Office_Excel_%282025%E2%80%93present%29.svg.png" alt="Excel">
                            </a>
                        </div>

                        <div class="ds-m365-pill">
                            <a href="https://www.office.com/launch/word" target="_blank" title="Word">
                                <img src="https://images.icon-icons.com/2397/PNG/512/microsoft_office_word_logo_icon_145724.png" alt="Word">
                            </a>
                        </div>

                        <div class="ds-m365-pill">
                            <a href="https://www.office.com/launch/powerpoint" target="_blank" title="PowerPoint">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Microsoft_PowerPoint_2013-2019_logo.svg/2255px-Microsoft_PowerPoint_2013-2019_logo.svg.png" alt="PowerPoint">
                            </a>
                        </div>

                        <div class="ds-m365-pill">
                            <a href="https://teams.microsoft.com" target="_blank" title="Microsoft Teams">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQychPQjxHP_T53JXZDtA3L5BHAu_REHa92NQ&s" alt="Microsoft Teams">
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
