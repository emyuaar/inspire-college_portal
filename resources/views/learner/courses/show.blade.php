@extends('layouts.learner')

@section('title', $course?->title ?? 'Course')

@section('content')
<div class="min-h-screen bg-slate-100">

    <main class="max-w-5xl mx-auto px-4 py-6 space-y-5">

        {{-- Back link --}}
        <a href="{{ route('portal.learner.courses.all') }}"
           class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700 mb-2">
            ← Back to my courses
        </a>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Course header card --}}
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">
                    {{ $course?->title ?? 'Course' }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Enrolment ID: #{{ $enrolment->id }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($enrolment->status_id == 2)
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium">
                        Approved
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                        Status: Pending
                    </span>
                @endif
            </div>
        </section>

        {{-- About / Overview --}}
        {{-- <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4 text-sm">
            @if(!empty($course?->overview))
                <div>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">Overview</h2>
                    <div class="prose prose-sm max-w-none text-slate-700">
                        {!! $course->overview !!}
                    </div>
                </div>
            @endif

            @if(!empty($course?->modules))
                <div class="pt-3 border-t border-slate-100">
                    <h2 class="text-base font-semibold text-slate-800 mb-2">Modules (syllabus)</h2>
                    <div class="prose prose-sm max-w-none text-slate-700">
                        {!! $course->modules !!}
                    </div>
                </div>
            @endif
        </section> --}}

        {{-- LMS content: Sections & Activities --}}
        @if($modules->count())
            <section class="space-y-4">
                @foreach($modules as $index => $module)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">
                                    {{ $module->title ?? 'Section ' . ($index + 1) }}
                                </h3>
                                @if($module->description)
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $module->description }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="px-5 py-3 text-sm">
                            @php
                                $hasItems = $module->lessons->count() || $module->assignments->count();
                            @endphp

                            @if(!$hasItems)
                                <p class="text-xs text-slate-400">
                                    Content will be added soon for this section.
                                </p>
                            @else
                                <ul class="space-y-2">

                                    {{-- Lessons / Pages --}}
                                    @foreach($module->lessons as $lesson)
                                        <li class="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2">
                                            <span class="mt-0.5 text-slate-500">📄</span>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-medium text-slate-900">
                                                        {{ $lesson->title }}
                                                    </p>
                                                </div>
                                                @if(!blank($lesson->content))
                                                    @php
                                                        // 1) strip tags
                                                        $plain = strip_tags($lesson->content);

                                                        // 2) decode entities: &nbsp; &amp; etc
                                                        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                                                        // 3) replace non-breaking spaces (NBSP) with normal spaces
                                                        $plain = str_replace("\xC2\xA0", ' ', $plain);

                                                        // 4) clean extra spaces
                                                        $plain = preg_replace('/\s+/', ' ', trim($plain));
                                                    @endphp

                                                    <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">
                                                        {{ \Illuminate\Support\Str::limit($plain, 120) }}
                                                    </p>
                                                @endif

                                                @php
                                                    $hasContent = !blank($lesson->content); // content exists => actual lesson page
                                                    $file = $lesson->file_path;
                                                    $isUrl = $file && \Illuminate\Support\Str::startsWith($file, ['http://','https://']);
                                                @endphp

                                                <div class="mt-1 flex flex-wrap gap-2 text-[11px] text-slate-500">

                                                    {{-- ✅ View lesson ONLY when content exists --}}
                                                    @if($hasContent)
                                                        <a href="{{ route('portal.learner.lessons.show', $lesson->id) }}"
                                                        class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                            👁️ View lesson
                                                        </a>
                                                    @endif

                                                    {{-- File link (SharePoint / local) --}}
                                                    @if(!blank($file))
                                                        @php
                                                            // If already an external URL (YouTube etc) keep it as is
                                                            $isUrl = \Illuminate\Support\Str::startsWith($file, ['http://','https://']);

                                                            // If your DB stores sharepoint item id or url separately, adjust here.
                                                            // Assumption: lesson has sharepoint_item_id OR sharepoint_url.
                                                            $hasSpItem = !blank($lesson->sharepoint_item_id ?? null);
                                                            $hasSpUrl  = !blank($lesson->sharepoint_url ?? null);
                                                        @endphp

                                                        @if($isUrl)
                                                            <a href="{{ $file }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                📂 View file
                                                            </a>
                                                        @elseif($hasSpItem)
                                                            <a href="{{ route('portal.learner.lesson.file.download', $lesson->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                📂 Download file
                                                            </a>

                                                            <a href="{{ route('portal.learner.lesson.file.inline', $lesson->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                👁️ View online
                                                            </a>
                                                        @elseif($hasSpUrl)
                                                            <a href="{{ $lesson->sharepoint_url }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                📂 View file
                                                            </a>
                                                        @else
                                                            <span class="text-[11px] text-slate-400">File link not available.</span>
                                                        @endif
                                                    @endif

                                                    {{-- External link/video --}}
                                                    @if(!blank($lesson->video_url))
                                                        <a href="{{ $lesson->video_url }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                            🔗 Open link / video
                                                        </a>
                                                    @endif

                                                </div>
                                            </div>
                                            <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                                Lesson
                                            </span>
                                        </li>
                                    @endforeach

                                    {{-- Assignments --}}
                                    @foreach($module->assignments as $assignment)
                                        @php
                                            $submission = $assignment->submissions->first();
                                            $brief      = $assignment->files->first();
                                        @endphp

                                        <li class="flex flex-col gap-2 rounded-lg bg-amber-50 px-3 py-3 border border-amber-100">
                                            <div class="flex items-start gap-2">
                                                <span class="mt-0.5 text-amber-500">📝</span>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-sm font-semibold text-slate-900">
                                                            {{ $assignment->title }}
                                                        </p>
                                                        @if($assignment->due_at)
                                                            <span class="text-[11px] text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2 py-0.5">
                                                                Due: {{ \Carbon\Carbon::parse($assignment->due_at)->format('d M Y') }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @if($assignment->instructions)
                                                        <p class="text-xs text-slate-700 mt-1">
                                                            {{ \Illuminate\Support\Str::limit(strip_tags($assignment->instructions), 180) }}
                                                        </p>
                                                    @endif

                                                    <div class="mt-2 flex flex-wrap gap-3 text-[11px] text-slate-600">
                                                        @if($brief)
                                                            <a href="{{ route('portal.learner.assignment.brief.local', $brief->id) }}"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                📥 Download assignment brief ({{ $brief->file_name }})
                                                            </a>
                                                        @endif

                                                        @if($assignment->max_marks)
                                                            <span>Max marks: {{ $assignment->max_marks }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Submission block --}}
                                            <div class="mt-2 border-t border-amber-100 pt-2">
                                                @if($submission)
                                                    <div class="flex items-center justify-between text-[11px] text-slate-600">
                                                        <div class="mt-1 flex flex-wrap gap-2">
                                                            <a href="{{ route('portal.learner.submission.view', $submission->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                📂 Download your submission
                                                            </a>

                                                            {{-- <a href="{{ route('portal.learner.submission.inline', $submission->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                👁️ View online
                                                            </a>

                                                            <a href="{{ route('portal.learner.submission.direct', $submission->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                                                🔗 Direct link
                                                            </a> --}}
                                                        </div>
                                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                                            {{ ucfirst($submission->status ?? 'submitted') }}
                                                        </span>
                                                    </div>

                                                    {{-- If you want to allow resubmission, show another form here --}}
                                                @else
                                                    <form action="{{ route('portal.learner.assignment.submit', $assignment->id) }}"
                                                          method="POST"
                                                          enctype="multipart/form-data"
                                                          class="flex flex-col sm:flex-row sm:items-center gap-2 text-[11px]">
                                                        @csrf
                                                        <div class="flex-1">
                                                            <label class="block text-slate-600 mb-1">
                                                                Upload your assignment file
                                                            </label>
                                                            <input type="file"
                                                                   name="submission_file"
                                                                   required
                                                                   class="block w-full text-[11px] text-slate-700 border border-slate-300 rounded-md px-2 py-1 bg-white">
                                                            <p class="text-[10px] text-slate-400 mt-0.5">
                                                                Accepted formats: Word, PDF, etc. Max 20MB.
                                                            </p>
                                                        </div>

                                                        <div class="pt-4 sm:pt-5">
                                                            <button type="submit"
                                                                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-full bg-indigo-600 text-white text-[11px] font-medium hover:bg-indigo-700">
                                                                Submit assignment
                                                            </button>
                                                        </div>
                                                    </form>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach

                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @else
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 text-sm text-slate-500">
                Course content will be available soon.
            </section>
        @endif

    </main>
</div>
@endsection
