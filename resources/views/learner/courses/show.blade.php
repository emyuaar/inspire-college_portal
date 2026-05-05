<x-app-layout page-title="{{ Str::limit($course?->title, 30) }}" active-page="courses">

    <div class="max-w-[1600px] mx-auto">

        {{-- Top Bar (Breadcrumb + Actions) --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.learner.courses.all') }}"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-ds-navy hover:border-ds-navy transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-ds-navy leading-tight">{{ $course?->title }}</h1>
                    <div class="flex flex-wrap items-center gap-3 mt-1 text-xs">
                        <span class="text-slate-500">SID: <strong
                                class="text-slate-700">DS{{ $user->id }}</strong></span>
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
                                <button type="button" onclick="scrollToUnit({{ $m->id }})"
                                    class="w-full text-left p-3 rounded-lg hover:bg-slate-50 transition-colors group focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ds-navy"
                                    data-nav-unit="{{ $m->id }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div
                                                class="text-sm font-semibold text-slate-700 group-hover:text-ds-navy transition-colors">
                                                Unit {{ $i + 1 }}: {{ $m->title }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ $m->lessons->count() }} Notes • {{ $m->assignments->count() }} Tasks
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-ds-pink" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
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
                                <div
                                    class="p-4 md:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <div>
                                            <h2 class="text-lg font-bold text-ds-navy">{{ $module->title }}</h2>
                                            @if($isEmpty)
                                                <span
                                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 uppercase tracking-wide">Empty
                                                    Unit</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                                        <span class="flex items-center gap-1"><span
                                                class="w-2 h-2 rounded-full bg-blue-400"></span> {{ $module->lessons->count() }}
                                            Notes</span>
                                        <span class="flex items-center gap-1"><span
                                                class="w-2 h-2 rounded-full bg-pink-400"></span>
                                            {{ $module->assignments->count() }} Tasks</span>
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
                                            <h3
                                                class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                </svg>
                                                Study Notes
                                            </h3>
                                            <div class="space-y-3">
                                                @foreach ($module->lessons as $lesson)
                                                    @php
                                                        $primaryUrl = !blank($lesson->video_url) ? $lesson->video_url : route('portal.learner.lessons.show', $lesson->id);
                                                        $primaryTarget = !blank($lesson->video_url) ? '_blank' : null;
                                                        $isFile = !blank($lesson->file_path);
                                                    @endphp
                                                    <div
                                                        class="group flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 hover:border-slate-200 hover:shadow-sm hover:bg-slate-50 transition-all bg-white">
                                                        <div class="flex items-start gap-4">
                                                            <div
                                                                class="mt-1 flex-shrink-0 w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                                                    stroke-linejoin="round" class="w-5 h-5">
                                                                    <!-- Book cover -->
                                                                    <path
                                                                        d="M3.5 5.5A2.5 2.5 0 0 1 6 3h12.5v18H6a2.5 2.5 0 0 0-2.5 2.5V5.5z" />

                                                                    <!-- Spine -->
                                                                    <path d="M7 3v18" />

                                                                    <!-- Page line -->
                                                                    <path d="M10 7h6" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <a href="{{ $primaryUrl }}" @if($primaryTarget)
                                                                target="{{ $primaryTarget }}" @endif
                                                                    class="font-bold text-slate-800 group-hover:text-ds-navy transition-colors block">
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
                                                            <x-ui.button href="{{ $primaryUrl }}" target="{{ $primaryTarget }}"
                                                                size="sm" variant="outline">
                                                                Open Lesson
                                                            </x-ui.button>
                                                            @if ($isFile)
                                                                <x-ui.button href="{{ $lesson->file_path }}" target="_blank" size="sm"
                                                                    variant="ghost" icon="download">
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
                                            <h3
                                                class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Assignments
                                            </h3>
                                            <div class="grid grid-cols-1 gap-4">
                                                @foreach ($module->assignments as $assignment)
                                                    @php
                                                        $submission = $assignment->submissions->first();
                                                        $attemptCount = $assignment->submissions->count();
                                                        $brief = $assignment->files->first();
                                                        $latestGrade = $assignment->latest_grade ?? null;

                                                        $res = $latestGrade ? strtolower($latestGrade->result) : null;
                                                        $isRefer = $res === 'refer';
                                                        $isPass = $res === 'pass';

                                                        // Check for Active Reset
                                                        // A reset is "active" if it was created AFTER the latest submission
                                                        // This means the admin clicked "Allow Re-attempt" and the learner has NOT yet submitted their new attempt.
                                                        $latestReset = $assignment->gradeResets->first();
                                                        $isActiveReset = false;
                                                        if ($latestReset) {
                                                            if (!$submission || $latestReset->reset_at > $submission->created_at) {
                                                                $isActiveReset = true;
                                                            }
                                                        }

                                                        // Check if we have a submission that is newer than the latest grade (Pending correct grading)
                                                        $submissionIsNewer = false;
                                                        if ($submission && $latestGrade && $latestGrade->graded_at) {
                                                            $submissionIsNewer = $submission->created_at->gt($latestGrade->graded_at);
                                                            // If submission is newer, we shouldn't show the old grade status as primary
                                                            // But we might want to keep history. For simplicity, if new sub exists, treated as "Submitted" state.
                                                        }

                                                        // Determine State & Banner
                                                        $bannerType = null;
                                                        $allowUpload = false;
                                                        $badgeVariant = 'neutral';
                                                        $badgeText = 'Pending';

                                                        if ($submissionIsNewer) {
                                                            $badgeVariant = 'success';
                                                            $badgeText = 'Submitted';
                                                            $allowUpload = false;
                                                        } elseif ($isPass) {
                                                            $badgeVariant = 'success';
                                                            $badgeText = 'Passed';
                                                            $allowUpload = false;
                                                        } elseif ($isRefer) {
                                                            $badgeVariant = 'warning';
                                                            $badgeText = 'Referred'; // Strict text

                                                            if ($attemptCount >= 2) {
                                                                // Max attempts used
                                                                $bannerType = 'max_attempts';
                                                                $allowUpload = false;
                                                            } elseif ($isActiveReset) {
                                                                // Reset active + Attempts < 2
                                                                $bannerType = 'reattempt_allowed';
                                                                $allowUpload = true;
                                                            } else {
                                                                // Referred, no reset yet
                                                                $bannerType = 'wait_approval';
                                                                $allowUpload = false;
                                                            }
                                                        } elseif ($submission) {
                                                            // Submitted, not graded yet
                                                            $badgeVariant = 'success';
                                                            $badgeText = 'Submitted';
                                                            $allowUpload = false;
                                                        } else {
                                                            // No submission
                                                            $badgeVariant = 'neutral';
                                                            $badgeText = 'Pending';
                                                            $allowUpload = true;
                                                        }
                                                    @endphp

                                                    <div class="bg-white rounded-xl border border-ds-pink/20 shadow-sm overflow-hidden">
                                                        {{-- Assignment Header --}}
                                                        <div
                                                            class="bg-ds-pink/5 p-4 flex items-center justify-between gap-3 border-b border-ds-pink/10">
                                                            <div class="flex items-center gap-3">
                                                                <div
                                                                    class="w-8 h-8 rounded-lg bg-ds-pink/10 text-ds-pink flex items-center justify-center">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h4 class="font-bold text-slate-800 text-sm">
                                                                        {{ $assignment->title }}
                                                                    </h4>
                                                                </div>
                                                            </div>

                                                            {{-- STATUS BADGE --}}
                                                            <x-ui.badge variant="{{ $badgeVariant }}" size="sm" rounded="full">
                                                                {{ $badgeText }}
                                                            </x-ui.badge>
                                                        </div>

                                                        <div class="p-4 bg-white space-y-4">
                                                            {{-- Assignment Brief (Always Visible) --}}
                                                            @if ($brief)
                                                                <a href="{{ $brief->file_path }}" target="_blank"
                                                                    class="flex items-center justify-between p-3 rounded-xl border-2 border-slate-100 bg-slate-50/50 hover:bg-white hover:border-ds-pink/30 hover:shadow-md transition-all group">
                                                                    <div class="flex items-center gap-3">
                                                                        <div
                                                                            class="w-10 h-10 rounded-lg bg-white border border-slate-100 text-ds-pink flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                            </svg>
                                                                        </div>
                                                                        <div>
                                                                            <div
                                                                                class="font-bold text-slate-800 text-sm group-hover:text-ds-pink transition-colors">
                                                                                Assignment Brief</div>
                                                                            <div class="text-xs text-slate-500 font-medium">Click to view
                                                                                instructions & requirements</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-slate-300 group-hover:text-ds-pink transition-colors">
                                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                                        </svg>
                                                                    </div>
                                                                </a>
                                                            @endif

                                                            {{-- FEEDBACK SECTION (Visible if Graded) --}}
                                                            @if ($latestGrade)
                                                                <details
                                                                    class="group border border-slate-200 rounded-lg bg-slate-50 overflow-hidden open:ring-2 open:ring-ds-navy/10">
                                                                    <summary
                                                                        class="w-full flex items-center justify-between p-3 bg-white border-b border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer list-none select-none">
                                                                        <span
                                                                            class="text-sm font-bold text-ds-navy flex items-center gap-2">
                                                                            <svg class="w-4 h-4 text-emerald-500" fill="none"
                                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                            </svg>
                                                                            Assessor Feedback
                                                                        </span>
                                                                        <span class="transform transition-transform group-open:rotate-180">
                                                                            <svg class="w-4 h-4 text-slate-400" fill="none"
                                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                                    stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                            </svg>
                                                                        </span>
                                                                    </summary>
                                                                    <div class="p-4 space-y-4 text-sm text-slate-600 bg-slate-50/50">
                                                                        <div
                                                                            class="flex items-center gap-4 text-xs text-slate-400 pb-2 border-b border-slate-200">
                                                                            <span>graded: <strong
                                                                                    class="text-slate-600">{{ $latestGrade->graded_at ? $latestGrade->graded_at->format('d M Y, H:i') : 'N/A' }}</strong></span>
                                                                            @if($latestGrade->assessor)
                                                                                <span>by: <strong
                                                                                        class="text-slate-600">{{ $latestGrade->assessor->name }}</strong></span>
                                                                            @endif
                                                                        </div>

                                                                        @if($latestGrade->feedback_text)
                                                                            <div
                                                                                class="prose prose-sm max-w-none text-slate-700 leading-relaxed bg-white p-3 rounded border border-slate-100">
                                                                                {!! nl2br(e($latestGrade->feedback_text)) !!}
                                                                            </div>
                                                                        @endif

                                                                        {{-- Attachments --}}
                                                                        @if($latestGrade->marking_sheet_path || $latestGrade->feedback_file_path)
                                                                            <div class="flex flex-wrap gap-2 pt-2">
                                                                                @php
                                                                                    // Use direct URL if valid (SharePoint), else route
                                                                                    // Marking Sheet
                                                                                    $msRaw = $latestGrade->marking_sheet_path;
                                                                                    $msIsUrl = filter_var($msRaw, FILTER_VALIDATE_URL);
                                                                                    $msHref = $msIsUrl ? $msRaw : route('portal.learner.assignment.grading.download', ['assignment' => $assignment->id, 'type' => 'marking_sheet']);

                                                                                    // Feedback File
                                                                                    $fbRaw = $latestGrade->feedback_file_path;
                                                                                    $fbIsUrl = filter_var($fbRaw, FILTER_VALIDATE_URL);
                                                                                    $fbHref = $fbIsUrl ? $fbRaw : route('portal.learner.assignment.grading.download', ['assignment' => $assignment->id, 'type' => 'feedback_file']);
                                                                                @endphp

                                                                                @if($latestGrade->marking_sheet_path)
                                                                                    <a href="{{ $msHref }}" target="_blank"
                                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-full hover:border-ds-navy hover:text-ds-navy transition-colors whitespace-nowrap">
                                                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                                            stroke="currentColor">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                                stroke-width="2"
                                                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                                        </svg>
                                                                                        Marking Sheet
                                                                                    </a>
                                                                                @endif

                                                                                @if($latestGrade->feedback_file_path)
                                                                                    <a href="{{ $fbHref }}" target="_blank"
                                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-full hover:border-ds-navy hover:text-ds-navy transition-colors whitespace-nowrap">
                                                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                                            stroke="currentColor">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                                stroke-width="2"
                                                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                                        </svg>
                                                                                        Feedback File
                                                                                    </a>
                                                                                @endif
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </details>
                                                            @endif

                                                            {{-- PREVIOUS SUBMISSION LINK --}}
                                                            @if ($submission)
                                                                <div
                                                                    class="flex items-center justify-between p-3 bg-amber-50 rounded border border-amber-100 text-sm">
                                                                    <div class="text-amber-800">
                                                                        <span class="block font-bold text-xs uppercase">Your
                                                                            Submission</span>
                                                                        <span class="text-xs">Uploaded:
                                                                            {{ $submission->created_at->format('d M Y, H:i') }}</span>
                                                                    </div>
                                                                    <x-ui.button
                                                                        href="{{ route('portal.learner.submission.view', $submission->id) }}"
                                                                        target="_blank" size="xs" variant="ghost">
                                                                        Download
                                                                    </x-ui.button>
                                                                </div>
                                                            @endif

                                                            {{-- BANNERS --}}
                                                            @if ($bannerType === 'wait_approval')
                                                                <div
                                                                    class="p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-start gap-3">
                                                                    <div class="mt-0.5 text-blue-500">
                                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="text-xs text-blue-900">
                                                                        <strong class="block font-bold">Please Wait for Approval</strong>
                                                                        Your submission was referred. Please review the feedback. If you are
                                                                        eligible for a second attempt, you will see an option here once
                                                                        approved by an assessor.
                                                                    </div>
                                                                </div>
                                                            @elseif ($bannerType === 'reattempt_allowed')
                                                                <div
                                                                    class="p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-start gap-3">
                                                                    <div class="mt-0.5 text-amber-600">
                                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="text-xs text-amber-900">
                                                                        <strong class="block font-bold">Second Attempt Available</strong>
                                                                        You have been granted a second attempt. Please upload your revised
                                                                        work below.
                                                                    </div>
                                                                </div>
                                                            @elseif ($bannerType === 'max_attempts')
                                                                <div
                                                                    class="p-3 bg-red-50 border border-red-100 rounded-lg flex items-start gap-3">
                                                                    <div class="mt-0.5 text-red-600">
                                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="text-xs text-red-900">
                                                                        <strong class="block font-bold">Maximum Attempts Reached</strong>
                                                                        Your second attempt was referred. You have used both attempts.
                                                                        Please contact support for further guidance.
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            {{-- UPLOAD FORM --}}
                                                            @if ($allowUpload)
                                                                <form
                                                                    action="{{ route('portal.learner.assignment.submit', $assignment->id) }}"
                                                                    method="POST" enctype="multipart/form-data"
                                                                    class="flex flex-col sm:flex-row sm:items-center gap-4 mt-4 pt-4 border-t border-slate-100">
                                                                    @csrf
                                                                    <div class="flex-1 w-full">
                                                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                                                            {{ $isActiveReset ? 'Upload Attempt 2' : 'Upload Submission' }}
                                                                        </label>
                                                                        <input type="file" name="submission_file" required
                                                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-ds-pink file:text-white hover:file:bg-pink-700 border border-slate-200 rounded-lg bg-slate-50">
                                                                        <p class="text-[10px] text-slate-400 mt-1">Accepts PDF, DOCX. Max
                                                                            20MB.</p>
                                                                    </div>
                                                                    <x-ui.button type="submit" variant="primary" size="sm">
                                                                        {{ $isActiveReset ? 'Submit Attempt 2' : 'Submit Assignment' }}
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
                <x-ui.button href="{{ route('portal.learner.courses.all') }}" variant="outline">Back to
                    Courses</x-ui.button>
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
                    if (b.dataset.navUnit == id) b.classList.add('bg-slate-100', 'ring-2', 'ring-ds-navy');
                });
            }
        }

        // Auto-select first unit on load if desktop
        document.addEventListener('DOMCon            tentLoaded', () => {
            const firstBtn = document.querySelector('[data-nav-unit]');
            if (firstBtn && window.innerWidth >= 1024) {
                firstBtn.classList.add('bg-slate-100', 'ring-2', 'ring-ds-navy');
            }
        });
    </script>

</x-app-layout>