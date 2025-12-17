@extends('layouts.learner')

@section('title', 'Learner Dashboard')

@section('content')
    <div class="max-w-6xl mx-auto space-y-4">

        {{-- Top welcome banner --}}
        <section
            class="bg-gradient-to-r from-sky-50 to-indigo-50 border border-sky-100 rounded-lg px-4 py-3 flex items-center justify-between gap-4 shadow-sm">
            <div class="flex-1">
                <p class="text-[11px] font-semibold tracking-wide text-sky-700 uppercase mb-0.5">Welcome</p>
                <h1 class="text-xl font-semibold text-slate-900 leading-tight">
                    {{ $user->first_name }} {{ $user->sur_name }}
                </h1>
                <p class="text-[13px] text-slate-500 mt-1">
                    Check your profile, complete required forms, and access your enrolled courses.
                </p>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-[12px] text-slate-600">
                <div class="flex flex-col items-end">
                    <span class="font-medium text-slate-700">Account</span>
                    <span>Learner</span>
                </div>
                <div class="h-8 w-px bg-slate-200"></div>
                <div class="flex flex-col items-end max-w-[220px]">
                    <span class="font-medium text-slate-700">Email</span>
                    <span class="truncate">{{ $user->email_address }}</span>
                </div>
            </div>
        </section>

        {{-- Enrolment Denied Notice --}}
        @if (!empty($isDenied) && $isDenied)
            <section class="bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[12px] font-semibold text-rose-800 mb-1">Enrolment Denied</p>
                        <p class="text-[13px] text-rose-700">
                            Your enrolment has been denied. Please refill your information correctly and submit again.
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <a href="{{ route('portal.profile.personal') }}"
                        class="text-[12px] px-3 py-1 rounded-full bg-white border border-rose-200 text-rose-700 hover:bg-rose-100">
                            Refill Personal
                        </a>
                        <a href="{{ route('portal.profile.rpl') }}"
                        class="text-[12px] px-3 py-1 rounded-full bg-white border border-rose-200 text-rose-700 hover:bg-rose-100">
                            Refill RPL
                        </a>
                        <a href="{{ route('portal.profile.disability') }}"
                        class="text-[12px] px-3 py-1 rounded-full bg-white border border-rose-200 text-rose-700 hover:bg-rose-100">
                            Refill Disability
                        </a>
                    </div>
                </div>
            </section>
        @endif

        {{-- Layout: left (profile + requirements) / right (courses) --}}
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.7fr)]">

            {{-- Left column --}}
            <div class="space-y-4">
                {{-- My Profile --}}
                <section class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold">
                                MP
                            </span>
                            My Profile
                        </h2>

                        {{-- Edit Profile Button --}}
                        <a href="{{ route('portal.settings.profile') }}"
                           class="inline-flex items-center gap-1 text-[12px] px-3 py-1 rounded-full border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.862 3.487l2.651 2.651M7 14l6.879-6.879a2 2 0 012.829 0l1.758 1.758a2 2 0 010 2.829L11.587 19H7v-5z" />
                            </svg>
                            Edit
                        </a>
                    </div>

                    <dl class="space-y-2 text-[13px] text-slate-700">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Name:</dt>
                            <dd class="font-medium text-right">
                                {{ $user->first_name }} {{ $user->sur_name }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Email:</dt>
                            <dd class="font-medium text-right truncate max-w-[260px]">
                                {{ $user->email_address }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Account Type:</dt>
                            <dd class="font-medium text-right">Learner</dd>
                        </div>
                    </dl>
                </section>

                {{-- Requirements --}}
                <section class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2.5">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-amber-600 text-xs font-semibold">
                                ✔
                            </span>
                            Requirements
                        </h2>
                        <p class="text-[11px] text-slate-400">
                            Complete these steps to start your learning.
                        </p>
                    </div>

                    <div class="space-y-2.5 text-[13px]">

                        {{-- Personal --}}
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-slate-100">
                            <div>
                                <p class="font-medium text-slate-700">Personal Information</p>
                                <p class="text-[11px] text-slate-400">
                                    Basic details and contact information.
                                </p>
                            </div>

                            <div class="text-right">
                                @if ($personalCompleted)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-100">
                                        Completed
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-medium border border-amber-100">
                                        Needs to fill
                                    </span>
                                    <a href="{{ route('portal.profile.personal') }}"
                                       class="block text-indigo-600 hover:underline text-[11px] mt-1">
                                        Fill Personal Information
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- RPL --}}
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-slate-100">
                            <div>
                                <p class="font-medium text-slate-700">RPL Information</p>
                                <p class="text-[11px] text-slate-400">
                                    Recognition of prior learning and experience.
                                </p>
                            </div>

                            <div class="text-right">
                                @if ($rplCompleted)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-100">
                                        Completed
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-medium border border-amber-100">
                                        Needs to fill
                                    </span>
                                    <a href="{{ route('portal.profile.rpl') }}"
                                       class="block text-indigo-600 hover:underline text-[11px] mt-1">
                                        Fill RPL Information
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Disability --}}
                        <div class="flex items-start justify-between gap-3 py-2">
                            <div>
                                <p class="font-medium text-slate-700">Disability Information</p>
                                <p class="text-[11px] text-slate-400">
                                    Support needs and reasonable adjustments.
                                </p>
                            </div>

                            <div class="text-right">
                                @if ($disabilityCompleted)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-100">
                                        Completed
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-medium border border-amber-100">
                                        Needs to fill
                                    </span>
                                    <a href="{{ route('portal.profile.disability') }}"
                                       class="block text-indigo-600 hover:underline text-[11px] mt-1">
                                        Fill Disability Information
                                    </a>
                                @endif
                            </div>
                        </div>

                    </div>
                </section>
            </div>

            {{-- Right column: My Courses --}}
            <section class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">

                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold">
                            📚
                        </span>
                        My Courses
                    </h2>

                    @if ($enrolments->count())
                        <span class="text-[11px] text-slate-400">
                            Showing {{ min(5, $enrolments->count()) }} of {{ $enrolments->count() }}
                        </span>
                    @endif
                </div>

                @php
                    $limitedEnrolments = $enrolments->take(5); // limit to 5 only
                @endphp

                @if ($limitedEnrolments->count())
                    <div class="divide-y divide-slate-100 text-[13px]">

                        @foreach ($limitedEnrolments as $enrolment)
                                @php
                                    $course   = $enrolment->course ?? null;
                                    $approved = (int)$enrolment->status_id === 2;
                                    $denied   = (int)$enrolment->status_id === 3;
                                @endphp

                            <div class="flex justify-between items-center py-2.5 gap-3">

                                <div class="flex-1">
                                    <p class="font-medium text-slate-800">
                                        @if ($approved && $course)
                                            <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                                               class="text-indigo-600 hover:underline">
                                                {{ $course->title }}
                                            </a>
                                        @else
                                            {{ $course?->title ?? 'Course #' . $enrolment->id }}
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Enrolment ID: {{ $enrolment->id }}
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-1">
                                    @if ($approved)
                                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-100">
                                            Approved
                                        </span>
                                        <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                                        class="text-[11px] text-indigo-600 hover:underline">
                                            Go to course
                                        </a>

                                    @elseif ($denied)
                                        <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-[11px] font-medium border border-rose-200">
                                            Denied - Refill Required
                                        </span>
                                        <a href="{{ route('portal.settings.profile') }}"
                                        class="text-[11px] text-rose-700 hover:underline">
                                            Update your details
                                        </a>

                                    @else
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px] font-medium border border-slate-200">
                                            Awaiting approval
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                    </div>

                    {{-- Show More Button --}}
                    @if ($enrolments->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('portal.learner.courses.all') }}"
                               class="text-[12px] text-indigo-600 hover:underline font-medium">
                                View all courses →
                            </a>
                        </div>
                    @endif
                @else
                    <p class="text-[13px] text-slate-500">
                        You are not enrolled in any course yet.
                    </p>
                @endif

            </section>

        </div>

    </div>
@endsection
