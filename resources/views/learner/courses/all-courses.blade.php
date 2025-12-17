@extends('layouts.learner')

@section('title', 'My Courses')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">My Courses</h1>
                <p class="text-xs text-slate-500 mt-1">
                    All courses you are enrolled on.
                </p>
            </div>

            <a href="{{ route('portal.learner.dashboard') }}"
               class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to dashboard
            </a>
        </div>

        {{-- Summary strip --}}
        <div class="flex items-center justify-between text-xs bg-slate-50 border border-slate-200 rounded-lg px-4 py-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs">
                    📚
                </span>
                <span class="text-slate-600">
                    Total enrolled: <span class="font-semibold">{{ $enrolments->count() }}</span>
                </span>
            </div>
        </div>

        @if ($enrolments->count())
            {{-- Courses list --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3 text-[11px] font-semibold uppercase text-slate-500">
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-6">Course</div>
                        <div class="col-span-2">Status</div>
                        <div class="col-span-2">Enrolment ID</div>
                        <div class="col-span-2 text-right">Action</div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 text-sm">
                    @foreach ($enrolments as $enrolment)
                        @php
                            $course   = $enrolment->course ?? null;
                            $approved = $enrolment->status_id == 2; // 2 = Approved
                        @endphp

                        <div class="px-5 py-3 hover:bg-slate-50/70 transition-colors">
                            <div class="grid grid-cols-12 gap-3 items-center">
                                {{-- {{dd($course->overview)}} --}}
                                {{-- Course --}}
                                <div class="col-span-6">
                                    <p class="font-medium text-slate-900">
                                        @if ($approved && $course)
                                            <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                                            class="text-indigo-600 hover:underline">
                                                {{ $course->title }}
                                            </a>
                                        @else
                                            {{ $course?->title ?? 'Course #' . $enrolment->id }}
                                        @endif
                                    </p>

                                    @if ($course?->category)
                                        <p class="text-[12px] text-slate-500 mt-0.5">
                                            {{ $course->category->title }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Status --}}
                                <div class="col-span-2">
                                    @if ($approved)
                                        <span class="inline-flex px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-100">
                                            Approved
                                        </span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px] font-medium border border-slate-200">
                                            Awaiting approval
                                        </span>
                                    @endif
                                </div>

                                {{-- Enrolment ID --}}
                                <div class="col-span-2 text-[11px] text-slate-500">
                                    #{{ $enrolment->id }}
                                </div>

                                {{-- Action --}}
                                <div class="col-span-2 flex justify-end">
                                    @if ($approved && $course)
                                        <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 rounded-full bg-indigo-600 text-white text-[11px] font-medium hover:bg-indigo-700">
                                            Go to course
                                        </a>
                                    @else
                                        <span class="text-[11px] text-slate-400">
                                            Waiting for approval
                                        </span>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 text-sm text-slate-500">
                You are not enrolled in any course yet.
            </div>
        @endif

    </div>
@endsection
