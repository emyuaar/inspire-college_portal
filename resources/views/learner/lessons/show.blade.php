@extends('layouts.learner')

@section('title', $lesson->title ?? 'Lesson')

@push('styles')
<style>
/* ===========================
   LMS Lesson Content Fix
   =========================== */

/* Only inside lesson content */
.lms-content table{
    width: 100% !important;
    border-collapse: collapse !important;
    table-layout: fixed; /* columns equal + controlled */
}

.lms-content td,
.lms-content th{
    padding: 8px 10px !important;   /* reduce extra gap */
    vertical-align: top;
    word-break: break-word;
}

/* remove big margins added by prose */
.lms-content .prose table { margin: 0 !important; }
.lms-content .prose p { margin: 0 0 10px !important; }

/* optional: remove extra top/bottom gaps */
.lms-content .prose { padding: 0 !important; }
.lms-content .prose > :first-child { margin-top: 0 !important; }
.lms-content .prose > :last-child { margin-bottom: 0 !important; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-100">
    <main class="max-w-5xl mx-auto px-4 py-6 space-y-5">

        {{-- Back link --}}
        <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
           class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            ← Back to course
        </a>

        {{-- Header --}}
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">
                        {{ $lesson->title }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $module->title ?? 'Module' }}
                    </p>
                </div>

                <span class="inline-flex px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">
                    Lesson
                </span>
            </div>

            {{-- Actions --}}
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                @php
                    $file  = $lesson->file_path;
                    $isUrl = $file && \Illuminate\Support\Str::startsWith($file, ['http://','https://']);
                @endphp

                @if($file)
                    <a href="{{ $isUrl ? $file : asset('storage/'.$file) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                        📂 View file
                    </a>
                @endif

                @if($lesson->video_url)
                    <a href="{{ $lesson->video_url }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                        🔗 Open link / video
                    </a>
                @endif
            </div>
        </section>

        {{-- CONTENT (HTML allowed) --}}
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            @if(!empty($lesson->content))
                <div class="lms-content">
                    <div class="prose prose-slate max-w-none">
                        {!! $lesson->content !!}
                    </div>
                </div>
            @else
                <p class="text-slate-500 text-sm">No lesson content available.</p>
            @endif
        </section>

        {{-- Prev / Next --}}
        <section class="flex items-center justify-between gap-3">
            @if($prev)
                <a href="{{ route('portal.learner.lessons.show', $prev->id) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-sm">
                    ← Previous
                </a>
            @else
                <div class="flex-1"></div>
            @endif

            @if($next)
                <a href="{{ route('portal.learner.lessons.show', $next->id) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-sm">
                    Next →
                </a>
            @else
                <div class="flex-1"></div>
            @endif
        </section>

    </main>
</div>
@endsection
