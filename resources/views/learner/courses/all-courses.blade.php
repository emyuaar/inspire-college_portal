<x-app-layout page-title="My Courses" active-page="courses">

    <div class="max-w-7xl mx-auto">

        {{-- Header & Stats --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Enrolled Courses</h1>
                <p class="text-sm text-slate-500 mt-1">Access your learning materials and track your progress.</p>
            </div>

            @if ($enrolments->count())
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-full shadow-sm text-sm font-medium text-slate-700">
                    <span
                        class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs">📚</span>
                    <span>Total Enrolled: <span class="font-bold text-ds-navy">{{ $enrolments->count() }}</span></span>
                </div>
            @endif
        </div>

        @if ($enrolments->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($enrolments as $enrolment)
                    @php
                        $course = $enrolment->course ?? null;
                        $state = $enrolment->access_state; // From Controller

                        $showContinue = $state->can_access;
                        $requirementsMet = $state->requirements_met;
                        $isVerified = $state->is_verified;
                        $blockReason = $state->block_reason;
                        $graceActive = $state->grace_active;
                        $graceUntil = $state->grace_until;
                        $dueInfo = $state->due_info;
                        $denied = $state->is_denied;
                        
                        $isPaid = $enrolment->payment_status_details['status'] === 'paid';
                        // Keep these for badges
                        $isActiveOrPaid = $isPaid || in_array(strtolower($enrolment->status->status ?? ''), ['active', 'paid', 'approved', 'installments_active']);
                        $isPaymentPending = !$isPaid && !$state->requirements_met && !$isVerified && !$denied;
                    @endphp

                    <x-ui.card class="h-full flex flex-col hover:shadow-md transition-shadow duration-200" padding="p-0">
                        <div class="p-5 flex-1 flex flex-col">

                            <div class="flex items-start justify-between gap-2 mb-3">
                                @if ($course?->category)
                                    <span
                                        class="inline-flex px-2 py-1 rounded bg-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                        {{ $course->category->title }}
                                    </span>
                                @else
                                    <span></span>
                                @endif

                                @if(!$requirementsMet)
                                    <x-ui.badge variant="brand" size="sm">Requirements Pending</x-ui.badge>
                                @elseif(!$isVerified)
                                    <x-ui.badge variant="neutral" size="sm">Under Review</x-ui.badge>
                                @elseif($blockReason)
                                    <x-ui.badge variant="error" size="sm">PAYMENT OVERDUE</x-ui.badge>
                                @elseif($graceActive)
                                    <x-ui.badge variant="warning" size="sm">GRACE PERIOD ACTIVE</x-ui.badge>
                                @elseif($isActiveOrPaid)
                                    <x-ui.badge variant="success" size="sm">Active</x-ui.badge>
                                @elseif($isPaymentPending)
                                    <x-ui.badge variant="warning" size="sm">Payment Pending</x-ui.badge>
                                @elseif($denied)
                                    <x-ui.badge variant="error" size="sm">Denied</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" size="sm">Pending</x-ui.badge>
                                @endif
                            </div>

                            <h3 class="font-bold text-lg text-slate-900 mb-2 line-clamp-2 leading-tight">
                                {{ $course?->title ?? 'Course #' . $enrolment->id }}
                            </h3>

                            <div
                                class="mt-auto pt-4 flex items-center justify-between text-xs text-slate-400 border-t border-slate-50">
                                <span>Enrolment ID: #{{ $enrolment->id }}</span>
                                {{-- Could add progress bar here if available --}}
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                            @if ($showContinue && $course)
                                <a href="{{ route('portal.learner.course.show', $enrolment->id) }}" class="w-full">
                                    <x-ui.button fullWidth size="sm">
                                        Continue Learning
                                        <x-slot name="icon"><svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg></x-slot>
                                    </x-ui.button>
                                </a>
                            @elseif ($blockReason)
                                <div class="flex flex-col items-center w-full">
                                    <span class="text-xs font-semibold text-red-600">Payment Overdue</span>
                                    <span class="text-[10px] text-slate-500">Please pay to restore access</span>
                                </div>
                            @elseif ($graceActive)
                                <div class="flex flex-col items-center w-full gap-1">
                                    <a href="{{ route('portal.learner.course.show', $enrolment->id) }}" class="w-full">
                                        <x-ui.button fullWidth size="sm">
                                            Continue Learning
                                        </x-ui.button>
                                    </a>
                                    <span class="text-[10px] text-amber-600 font-medium">
                                        Grace period ends {{ \Carbon\Carbon::parse($graceUntil)->format('d M Y') }}
                                    </span>
                                </div>
                            @elseif ($isPaymentPending)
                                <span class="text-xs font-semibold text-amber-600 w-full text-center">Contact Partner for
                                    Payment</span>
                            @elseif (!$requirementsMet)
                                <x-ui.button fullWidth size="sm" variant="outline" href="{{ route('portal.profile.personal') }}">
                                    Complete Requirements
                                </x-ui.button>
                            @elseif (!$isVerified)
                                <span class="text-xs font-semibold text-slate-500 italic w-full text-center">Awaiting Approval</span>
                            @elseif ($denied)
                                <x-ui.button variant="outline" fullWidth size="sm" class="opacity-50 cursor-not-allowed">
                                    Access Denied
                                </x-ui.button>
                            @else
                                <x-ui.button variant="outline" fullWidth size="sm" class="bg-white" disabled>
                                    Awaiting Status
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-300">
                <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900">No Courses Yet</h3>
                <p class="text-slate-500 mt-1 mb-6">You are not enrolled in any courses at the moment.</p>
                <x-ui.button href="{{ route('portal.learner.dashboard') }}" variant="outline">
                    Return to Dashboard
                </x-ui.button>
            </div>
        @endif

    </div>

</x-app-layout>