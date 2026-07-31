<x-app-layout page-title="{{ Str::limit($lesson->title, 30) }}" active-page="courses">

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Top Bar / Back Link --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-ds-navy transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Course
            </a>

            <div class="text-xs text-slate-400 font-medium">
                {{ $module->title ?? 'Module Content' }}
            </div>
        </div>

        {{-- Lesson Card --}}
        <x-ui.card padding="p-0" class="overflow-hidden">

            {{-- Header --}}
            <div class="bg-slate-50 border-b border-slate-100 p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-ds-navy/10 text-ds-navy uppercase tracking-wide">
                                Lesson
                            </span>
                            @if($lesson->video_url)
                                <span
                                    class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-pink-100 text-ds-pink uppercase tracking-wide">
                                    Video
                                </span>
                            @endif
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-ds-navy leading-tight">
                            {{ $lesson->title }}
                        </h1>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-2">
                        @php
                            $file = $lesson->file_path;
                            $isUrl = $file && \Illuminate\Support\Str::startsWith($file, ['http://', 'https://']);
                        @endphp

                        @if($file)
                            <x-ui.button href="{{ $isUrl ? $file : route('portal.learner.lesson.file.download', $lesson->id) }}" 
                                target="_blank"
                                variant="outline" size="sm">
                                <x-slot name="icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </x-slot>
                                View File
                            </x-ui.button>
                        @endif

                        @if($lesson->video_url)
                            <x-ui.button href="{{ $lesson->video_url }}" target="_blank" variant="primary" size="sm">
                                <x-slot name="icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-slot>
                                Open Video
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-6 md:p-8 bg-white min-h-[300px]">
                @if(!empty($lesson->content))
                    <x-protected-content-guard 
                        :learner-name="auth()->user()->name" 
                        :learner-email="auth()->user()->email" 
                        :course-name="$module->course->title ?? ''"
                        :course-id="$module->course_id ?? null"
                        :lesson-id="$lesson->id ?? null">
                        <div class="prose prose-slate max-w-none prose-headings:text-ds-navy prose-a:text-ds-pink hover:prose-a:text-pink-700 prose-img:rounded-xl">
                            {!! \App\Helpers\ContentObfuscator::obfuscate($lesson->content) !!}
                        </div>
                    </x-protected-content-guard>
                @else
                    <div class="flex flex-col items-center justify-center h-40 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm">No additional written content for this lesson.</p>
                        @if($lesson->video_url || $lesson->file_path)
                            <p class="text-xs text-slate-400 mt-1">Check the buttons above for materials.</p>
                        @endif
                    </div>
                @endif
            </div>

        </x-ui.card>

        {{-- Navigation Footer --}}
        <div class="grid grid-cols-2 gap-4">
            @if($prev)
                <a href="{{ route('portal.learner.lessons.show', $prev->id) }}"
                    class="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200 hover:border-ds-navy/30 hover:shadow-md transition-all group text-left">
                    <div
                        class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-ds-navy group-hover:text-white transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </div>
                    <div>
                        <div
                            class="text-[10px] uppercase font-bold text-slate-400 group-hover:text-ds-navy transition-colors">
                            Previous</div>
                        <div class="text-sm font-bold text-slate-700 group-hover:text-ds-navy truncate transition-colors">
                            {{ $prev->title }}</div>
                    </div>
                </a>
            @else
                <div></div>
            @endif

            @if($next)
                <a href="{{ route('portal.learner.lessons.show', $next->id) }}"
                    class="flex items-center justify-end gap-3 p-4 rounded-xl bg-white border border-slate-200 hover:border-ds-navy/30 hover:shadow-md transition-all group text-right">
                    <div>
                        <div
                            class="text-[10px] uppercase font-bold text-slate-400 group-hover:text-ds-navy transition-colors">
                            Next Lesson</div>
                        <div class="text-sm font-bold text-slate-700 group-hover:text-ds-navy truncate transition-colors">
                            {{ $next->title }}</div>
                    </div>
                    <div
                        class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-ds-navy group-hover:text-white transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @else
                <div></div>
            @endif
        </div>

    </div>

</x-app-layout>