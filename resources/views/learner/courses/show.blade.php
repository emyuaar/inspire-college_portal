<x-app-layout page-title="{{ Str::limit($course?->title, 30) }}" active-page="courses">

    <div class="max-w-[1600px] mx-auto">

        {{-- Top Bar (Breadcrumb + Actions) --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.learner.courses.all') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-ds-navy hover:border-ds-navy transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <div>
                     <h1 class="text-xl md:text-2xl font-bold text-ds-navy leading-tight">{{ $course?->title }}</h1>
                     <div class="flex flex-wrap items-center gap-3 mt-1 text-xs">
                        <span class="text-slate-500">SID: <strong class="text-slate-700">DS{{ $user->id }}</strong></span>
                         @if ((int) $enrolment->status_id === 2)
                            <x-ui.badge variant="success" size="sm">Approved</x-ui.badge>
                        @elseif((int) $enrolment->status_id === 3)
                            <x-ui.badge variant="error" size="sm">Denied</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning" size="sm">Pending</x-ui.badge>
                        @endif
                     </div>
                </div>
            </div>
             
             <div class="hidden md:block">
                 <x-ui.button href="{{ route('portal.learner.courses.all') }}" variant="outline" size="sm">
                    View All Courses
                </x-ui.button>
            </div>
        </div>

        @if ($modules->count())
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                {{-- LEFT SIDEBAR (Navigation) --}}
                <div class="hidden lg:block lg:col-span-3 sticky top-24">
                    <x-ui.card class="overflow-hidden" padding="p-0">
                         <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <h3 class="font-bold text-ds-navy text-sm">Course Navigation</h3>
                            <p class="text-xs text-slate-500">Select a unit to view content</p>
                        </div>
                        <div class="max-h-[calc(100vh-200px)] overflow-y-auto p-2 space-y-1">
                            @foreach ($modules as $i => $m)
                                <button type="button" 
                                    onclick="scrollToUnit({{ $m->id }})"
                                    class="w-full text-left p-3 rounded-lg hover:bg-slate-50 transition-colors group focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ds-navy"
                                    data-nav-unit="{{ $m->id }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-700 group-hover:text-ds-navy transition-colors">
                                                Unit {{ $i + 1 }}: {{ $m->title }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ $m->lessons->count() }} Notes • {{ $m->assignments->count() }} Tasks
                                            </div>
                                        </div>
                                         <svg class="w-4 h-4 text-slate-300 group-hover:text-ds-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>

                {{-- RIGHT CONTENT (Units) --}}
                <div class="lg:col-span-9 space-y-6">
                    @foreach ($modules as $index => $module)
                        @php
                            $isEmpty = $module->lessons->count() + $module->assignments->count() === 0;
                        @endphp

                        <div id="unit-{{ $module->id }}" class="scroll-mt-24 transition-opacity duration-300">
                            <x-ui.card class="overflow-hidden" padding="p-0">
                                
                                {{-- Unit Header --}}
                                <div class="p-4 md:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <div>
                                            <h2 class="text-lg font-bold text-ds-navy">{{ $module->title }}</h2>
                                            @if($isEmpty)
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 uppercase tracking-wide">Empty Unit</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400"></span> {{ $module->lessons->count() }} Notes</span>
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-pink-400"></span> {{ $module->assignments->count() }} Tasks</span>
                                    </div>
                                </div>

                                <div class="p-4 md:p-6 space-y-8">
                                    @if ($isEmpty)
                                        <div class="text-center py-8">
                                            <p class="text-sm text-slate-400 italic">No content available for this unit yet.</p>
                                        </div>
                                    @endif

                                    {{-- LESSONS --}}
                                    @if ($module->lessons->count())
                                        <div>
                                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                                Study Notes
                                            </h3>
                                            <div class="space-y-3">
                                                @foreach ($module->lessons as $lesson)
                                                    @php
                                                        $primaryUrl = !blank($lesson->video_url) ? $lesson->video_url : route('portal.learner.lessons.show', $lesson->id);
                                                        $primaryTarget = !blank($lesson->video_url) ? '_blank' : null;
                                                        $isFile = !blank($lesson->file_path);
                                                    @endphp
                                                    <div class="group flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 hover:border-slate-200 hover:shadow-sm hover:bg-slate-50 transition-all bg-white">
                                                        <div class="flex items-start gap-4">
                                                            <div class="mt-1 flex-shrink-0 w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                                        fill="none" stroke="currentColor" stroke-width="1.6"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="w-5 h-5">
                                                                    <!-- Book cover -->
                                                                    <path d="M3.5 5.5A2.5 2.5 0 0 1 6 3h12.5v18H6a2.5 2.5 0 0 0-2.5 2.5V5.5z"/>
                                                                    
                                                                    <!-- Spine -->
                                                                    <path d="M7 3v18"/>
                                                                    
                                                                    <!-- Page line -->
                                                                    <path d="M10 7h6"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <a href="{{ $primaryUrl }}" @if($primaryTarget) target="{{ $primaryTarget }}" @endif class="font-bold text-slate-800 group-hover:text-ds-navy transition-colors block">
                                                                    {{ $lesson->title }}
                                                                </a>
                                                                <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                                                    <span>{{ !blank($lesson->video_url) ? 'Lesson' : 'Reading Material' }}</span>
                                                                    @if($isFile)
                                                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                                        <span>Includes files</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2 pl-12 md:pl-0">
                                                            <x-ui.button href="{{ $primaryUrl }}" target="{{ $primaryTarget }}" size="sm" variant="outline">
                                                                Open Lesson
                                                            </x-ui.button>
                                                             @if ($isFile)
                                                                <x-ui.button href="{{ $lesson->file_path }}" target="_blank" size="sm" variant="ghost" icon="download">
                                                                    File
                                                                </x-ui.button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ASSIGNMENTS --}}
                                    @if ($module->assignments->count())
                                        <div class="@if($module->lessons->count()) pt-6 border-t border-slate-100 @endif">
                                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                Assignments
                                            </h3>
                                            <div class="grid grid-cols-1 gap-4">
                                                @foreach ($module->assignments as $assignment)
                                                    @php
                                                        $submission = $assignment->submissions->first();
                                                        $brief = $assignment->files->first();
                                                    @endphp

                                                    <div class="bg-white rounded-xl border border-ds-pink/20 shadow-sm overflow-hidden">
                                                        {{-- Assignment Header --}}
                                                        <div class="bg-ds-pink/5 p-4 flex items-center justify-between gap-3 border-b border-ds-pink/10">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-8 h-8 rounded-lg bg-ds-pink/10 text-ds-pink flex items-center justify-center">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                </div>
                                                                <div>
                                                                    <h4 class="font-bold text-slate-800 text-sm">{{ $assignment->title }}</h4>
                                                                    {{-- Link removed --}}
                                                                </div>
                                                            </div>
                                                             @if ($submission)
                                                                <x-ui.badge variant="success" size="sm" rounded="full">
                                                                    {{ ucfirst($submission->status ?? 'submitted') }}
                                                                </x-ui.badge>
                                                            @else
                                                                <x-ui.badge variant="warning" size="sm" rounded="full">Pending</x-ui.badge>
                                                            @endif
                                                        </div>

                                                        <div class="p-4 bg-white space-y-4">
                                                            {{-- Assignment Brief Call-to-Action --}}
                                                            @if ($brief)
                                                                <a href="{{ $brief->file_path }}" target="_blank" class="flex items-center justify-between p-3 rounded-xl border-2 border-slate-100 bg-slate-50/50 hover:bg-white hover:border-ds-pink/30 hover:shadow-md transition-all group">
                                                                    <div class="flex items-center gap-3">
                                                                        <div class="w-10 h-10 rounded-lg bg-white border border-slate-100 text-ds-pink flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                                        </div>
                                                                        <div>
                                                                            <div class="font-bold text-slate-800 text-sm group-hover:text-ds-pink transition-colors">Assignment Brief</div>
                                                                            <div class="text-xs text-slate-500 font-medium">Click to view instructions & requirements</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-slate-300 group-hover:text-ds-pink transition-colors">
                                                                         <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                                                    </div>
                                                                </a>
                                                            @endif
                                                            @if ($submission)
                                                                <div class="flex items-center justify-between">
                                                                    <div class="text-sm text-slate-600">
                                                                        <span class="block font-medium text-slate-800">Submission Received</span>
                                                                        <span class="text-xs">Uploaded on {{ $submission->created_at->format('d M Y, H:i') }}</span>
                                                                    </div>
                                                                <x-ui.button href="{{ route('portal.learner.submission.view', $submission->id) }}" target="_blank" size="sm" variant="outline">
                                                                    <x-slot name="icon">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                                    </x-slot>
                                                                    Download
                                                                </x-ui.button>
                                                                </div>
                                                            @else
                                                                <form action="{{ route('portal.learner.assignment.submit', $assignment->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-4">
                                                                    @csrf
                                                                    <div class="flex-1 w-full">
                                                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Upload Submission</label>
                                                                        <input type="file" name="submission_file" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-ds-pink file:text-white hover:file:bg-pink-700 border border-slate-200 rounded-lg bg-slate-50">
                                                                        <p class="text-[10px] text-slate-400 mt-1">Accepts PDF, DOCX. Max 20MB.</p>
                                                                    </div>
                                                                    <x-ui.button type="submit" variant="primary" size="sm">
                                                                        Submit Assignment
                                                                    </x-ui.button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </x-ui.card>
                        </div>
                    @endforeach
                </div>

            </div>
        @else
            <x-ui.card class="text-center py-12">
                <p class="text-slate-500 mb-4">Course content is currently unavailable.</p>
                <x-ui.button href="{{ route('portal.learner.courses.all') }}" variant="outline">Back to Courses</x-ui.button>
            </x-ui.card>
        @endif

    </div>

    <script>
        function scrollToUnit(id) {
            const el = document.getElementById('unit-' + id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Simple highlight effect
                document.querySelectorAll('[data-nav-unit]').forEach(b => {
                    b.classList.remove('bg-slate-100', 'ring-2', 'ring-ds-navy');
                    if(b.dataset.navUnit == id) b.classList.add('bg-slate-100', 'ring-2', 'ring-ds-navy');
                });
            }
        }
        
        // Auto-select first unit on load if desktop
        document.addEventListener('DOMContentLoaded', () => {
             const firstBtn = document.querySelector('[data-nav-unit]');
             if(firstBtn && window.innerWidth >= 1024) {
                 firstBtn.classList.add('bg-slate-100', 'ring-2', 'ring-ds-navy');
             }
        });
    </script>

</x-app-layout>
