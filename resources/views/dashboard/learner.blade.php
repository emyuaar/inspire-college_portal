<x-app-layout page-title="Learner Dashboard" active-page="dashboard">

    {{-- WELCOME HEADER --}}
    <x-ui.card class="bg-gradient-to-r from-ds-navy to-[#0F4C81] text-white border-none overflow-hidden relative">
        <div
            class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none">
        </div>
        <div
            class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-ds-pink/20 rounded-full blur-2xl pointer-events-none">
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span
                        class="px-2 py-0.5 rounded-full bg-white/10 text-[10px] font-bold uppercase tracking-wider text-white/90 border border-white/10">
                        Welcome Back
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    {{ $user->first_name }} {{ $user->middle_name }} {{ $user->sur_name }}
                </h1>
                <p class="text-blue-100/80 text-sm max-w-xl leading-relaxed">
                    Check your profile, complete required forms, and access your enrolled courses.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <div
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-black/20 text-xs font-medium border border-white/10 backdrop-blur-sm text-blue-50">
                        <span class="opacity-70 mr-2">Student ID:</span>
                        <span class="font-bold text-white">DS{{ $user->id }}</span>
                    </div>
                    <div
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-black/20 text-xs font-medium border border-white/10 backdrop-blur-sm text-blue-50 overflow-x-auto">
                        <span class="opacity-70 mr-2 whitespace-nowrap">Email:</span>
                        <span class="font-bold text-white">
                            {{ $user->email_address }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Optional: Quick Action or Image --}}
            {{-- <div class="hidden md:block"> ... </div> --}}
        </div>
    </x-ui.card>

    {{-- ENROLMENT DENIED ALERT --}}
    @if (!empty($isDenied) && $isDenied)
        <x-ui.alert variant="error" title="Enrolment Denied">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <p>Your enrolment has been denied. Please update your information and submit again.</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="danger" size="sm" href="{{ route('portal.profile.personal') }}">
                        Refill Personal
                    </x-ui.button>
                    <x-ui.button variant="danger" size="sm" href="{{ route('portal.profile.rpl') }}">
                        Refill RPL
                    </x-ui.button>
                    <x-ui.button variant="danger" size="sm" href="{{ route('portal.profile.disability') }}">
                        Refill Disability
                    </x-ui.button>
                </div>
            </div>
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LEFT COLUMN (Profile & Requirements) --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- MY PROFILE --}}
            <x-ui.card title="My Profile" subtitle="Your personal details">
                <x-slot name="actions">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('portal.settings.profile') }}">
                        <x-slot name="icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </x-slot>
                    </x-ui.button>
                </x-slot>

                <dl class="space-y-4 text-sm">
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="text-slate-500">Full Name</dt>
                        <dd class="font-bold text-slate-800 text-right">{{ $user->first_name }} {{ $user->sur_name }}
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="text-slate-500">Email Address</dt>
                        <dd class="font-bold text-slate-800 text-right max-w-[180px]"
                            title="{{ $user->email_address }}">
                            {{ $user->email_address }}
                        </dd>
                    </div>
                    <div class="flex justify-between pt-1">
                        <dt class="text-slate-500">Student ID</dt>
                        <dd class="font-bold text-slate-800 text-right">DS{{ $user->id }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            {{-- REQUIREMENTS --}}
            <x-ui.card title="Requirements" subtitle="Complete to start learning">
                <div class="space-y-4">

                    {{-- Personal --}}
                    <div class="flex items-start justify-between gap-3 group">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-slate-700 group-hover:text-ds-navy transition-colors">
                                Personal Information</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Basic details and contacts.</p>
                        </div>
                        @if ($personalCompleted)
                            <x-ui.badge variant="success" size="sm">Completed</x-ui.badge>
                        @else
                            <div class="text-right">
                                <x-ui.badge variant="brand" size="sm">To Do</x-ui.badge>
                                <a href="{{ route('portal.profile.personal') }}"
                                    class="block text-[10px] font-bold text-ds-pink hover:underline mt-1">Fill Now →</a>
                            </div>
                        @endif
                    </div>

                    <hr class="border-slate-50">

                    {{-- RPL --}}
                    <div class="flex items-start justify-between gap-3 group">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-slate-700 group-hover:text-ds-navy transition-colors">RPL
                                Information</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Recognition of prior learning.</p>
                        </div>
                        @if ($rplCompleted)
                            <x-ui.badge variant="success" size="sm">Completed</x-ui.badge>
                        @else
                            <div class="text-right">
                                <x-ui.badge variant="brand" size="sm">To Do</x-ui.badge>
                                <a href="{{ route('portal.profile.rpl') }}"
                                    class="block text-[10px] font-bold text-ds-pink hover:underline mt-1">Fill Now →</a>
                            </div>
                        @endif
                    </div>

                    <hr class="border-slate-50">

                    {{-- Disability --}}
                    <div class="flex items-start justify-between gap-3 group">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-slate-700 group-hover:text-ds-navy transition-colors">
                                Disability Info</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Support needs & adjustments.</p>
                        </div>
                        @if ($disabilityCompleted)
                            <x-ui.badge variant="success" size="sm">Completed</x-ui.badge>
                        @else
                            <div class="text-right">
                                <x-ui.badge variant="brand" size="sm">To Do</x-ui.badge>
                                <a href="{{ route('portal.profile.disability') }}"
                                    class="block text-[10px] font-bold text-ds-pink hover:underline mt-1">Fill Now →</a>
                            </div>
                        @endif
                    </div>

                </div>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN (Courses) --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- COURSES --}}
            <x-ui.card title="My Courses" subtitle="Your active enrolments">
                <x-slot name="actions">
                    @if ($enrolments->count())
                        <x-ui.badge variant="neutral">{{ $enrolments->count() }} Total</x-ui.badge>
                    @endif
                </x-slot>

                @if ($enrolments->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($enrolments->take(3) as $enrolment)
                            @php
                                $showContinue = false; // Safe default
                                $course = $enrolment->course ?? null;

                                $status = strtolower($enrolment->status->status ?? '');
                                $requirementsMet = $user->areRequirementsMet();
                                $isVerified = $user->isVerified();

                                $latestOrder = $enrolment->latestOrder;
                                $isOrderPaid = $latestOrder && (int) $latestOrder->status_id === 1;

                                $statusStr = strtolower($enrolment->status->status ?? '');
                                $isPaid = $latestOrder ? $isOrderPaid : in_array($statusStr, ['active', 'paid', 'approved']);

                                if ($latestOrder && !$isOrderPaid) {
                                    $statusStr = 'pending-payment';
                                }

                                $isActiveOrPaid = $isPaid || in_array($statusStr, ['active', 'paid', 'approved']);
                                $showContinue = $isVerified && $isActiveOrPaid;

                                $denied = $statusStr === 'denied';

                                // Installment Access Logic (Unified Status)
                                $accessStatus = $enrolment->installment_access_status;
                                $blockReason = null;
                                $dueInfo = null;
                                $graceActive = $accessStatus->grace_active ?? false;
                                $graceUntil = $accessStatus->grace_until ?? null;

                                if (!$accessStatus->allowed) {
                                    $showContinue = false;
                                    $blockReason = $accessStatus->reason;
                                    $dueInfo = $accessStatus->due_info;
                                }
                            @endphp

                            {{-- ✅ ITEM WRAPPER --}}
                            <div class="rounded-2xl border border-slate-100 bg-white p-4 hover:shadow-sm transition">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            @if(!$requirementsMet)
                                                <x-ui.badge variant="brand" size="sm">Requirements Pending</x-ui.badge>
                                            @elseif(!$isVerified)
                                                <x-ui.badge variant="neutral" size="sm">Under Review (CRM)</x-ui.badge>
                                            @elseif($blockReason)
                                                <x-ui.badge variant="error" size="sm">PAYMENT OVERDUE</x-ui.badge>
                                            @elseif($graceActive)
                                                <x-ui.badge variant="warning" size="sm">GRACE PERIOD ACTIVE</x-ui.badge>
                                            @elseif($isPaid || in_array($statusStr, ['active', 'paid', 'approved']))
                                                <x-ui.badge variant="success" size="sm">Active</x-ui.badge>
                                                @php $showContinue = true; @endphp
                                            @elseif($latestOrder && !$isOrderPaid)
                                                <x-ui.badge variant="warning" size="sm">Payment Pending</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral" size="sm">Pending</x-ui.badge>
                                            @endif

                                            @if($denied)
                                                <x-ui.badge variant="error" size="sm">Denied</x-ui.badge>
                                            @endif

                                            <span class="text-[10px] text-slate-400 font-medium">#{{ $enrolment->id }}</span>
                                        </div>

                                        <h3 class="font-bold text-slate-800 text-sm md:text-base mb-1">
                                            @if ($showContinue && $course)
                                                <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                                                    class="hover:text-ds-navy transition-colors">
                                                    {{ $course->title }}
                                                </a>
                                            @else
                                                {{ $course?->title ?? 'Course #' . $enrolment->id }}
                                            @endif
                                        </h3>

                                        <p class="text-xs text-slate-500">Student ID: DS{{ $user->id }}</p>
                                    </div>

                                    <div class="shrink-0 self-end sm:self-center">
                                        @if ($showContinue)
                                            <x-ui.button size="sm" href="{{ route('portal.learner.course.show', $enrolment->id) }}">
                                                Continue Learning
                                            </x-ui.button>
                                        @elseif ($blockReason)
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-semibold text-red-600">Payment Overdue</span>
                                                <span class="text-[10px] text-slate-500">Please pay to restore access</span>
                                            </div>
                                        @elseif ($graceActive)
                                            <div class="flex flex-col items-end">
                                                <x-ui.button size="sm"
                                                    href="{{ route('portal.learner.course.show', $enrolment->id) }}">
                                                    Continue Learning
                                                </x-ui.button>
                                                <span class="text-[10px] text-amber-600 mt-1 font-medium">
                                                    Grace period ends {{ \Carbon\Carbon::parse($graceUntil)->format('d M Y') }}
                                                </span>
                                            </div>
                                        @elseif ($latestOrder && !$isOrderPaid)
                                            <span class="text-xs font-semibold text-amber-600">Please contact Partner</span>
                                        @elseif (!$requirementsMet)
                                            <x-ui.button size="sm" variant="outline" href="{{ route('portal.profile.personal') }}">
                                                Complete Requirements
                                            </x-ui.button>
                                        @elseif (!$isVerified)
                                            <span class="text-xs font-semibold text-slate-500 italic">Awaiting Admin Approval</span>
                                        @elseif ($denied)
                                            <x-ui.button size="sm" variant="outline" href="{{ route('portal.settings.profile') }}"
                                                class="text-red-600 border-red-200 hover:bg-red-50">
                                                Update Details
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($enrolments->count() > 3)
                        <div class="mt-4 text-center">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('portal.learner.courses.all') }}">
                                View All Courses
                            </x-ui.button>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <div class="mx-auto w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm">You are not enrolled in any courses yet.</p>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
    {{-- ✅ MS 365 Card (Below both columns, inside the same grid) --}}
    <div class="lg:col-span-12">
        <x-ui.card padding="p-0">
            <div class="p-6 flex flex-col md:flex-row gap-6 items-center">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg"
                            alt="Microsoft" class="h-4">
                        @if($user->ms_license_assigned)
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">Activated</span>
                        @else
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">Pending
                                Activation</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-slate-800 text-lg mb-1">Microsoft 365 Web Access</h3>
                    <p class="text-sm text-slate-500">
                        Use your learner account to access Word, Excel, PowerPoint, and Teams for your studies.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 justify-center">
                    {{-- Teams --}}
                    <a href="https://www.office.com" target="_blank" title="Office Portal"
                        class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <svg width="24" height="24" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none">
                            <path fill="#F35325" d="M1 1h6.5v6.5H1V1z" />
                            <path fill="#81BC06" d="M8.5 1H15v6.5H8.5V1z" />
                            <path fill="#05A6F0" d="M1 8.5h6.5V15H1V8.5z" />
                            <path fill="#FFBA08" d="M8.5 8.5H15V15H8.5V8.5z" />
                        </svg>
                    </a>

                    {{-- Outlook --}}
                    <a href="https://outlook.office.com" target="_blank" title="Outlook"
                        class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <mask id="mask0_353_14160" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="2"
                                y="2" width="19" height="19">
                                <path d="M20.999 2.99951H2.99902V20.9995H20.999V2.99951Z" fill="white" />
                            </mask>
                            <g mask="url(#mask0_353_14160)">
                                <path
                                    d="M15.4647 4.67096L3.98719 11.9462L3.00012 10.389V9.04715C3.00012 8.55861 3.24745 8.10328 3.65728 7.83735L10.3292 3.50807C11.3457 2.84848 12.655 2.84838 13.6716 3.50781L15.4647 4.67096Z"
                                    fill="url(#paint0_linear_353_14160)" />
                                <path
                                    d="M13.569 3.44415C13.6035 3.46467 13.6378 3.48593 13.6717 3.50792L18.8787 6.88553L5.96808 15.0691L3.98694 11.9436L13.4621 5.92605C14.3595 5.35606 14.3988 4.07383 13.569 3.44415Z"
                                    fill="url(#paint1_linear_353_14160)" />
                                <path
                                    d="M13.569 3.44415C13.6035 3.46467 13.6378 3.48593 13.6717 3.50792L18.8787 6.88553L5.96808 15.0691L3.98694 11.9436L13.4621 5.92605C14.3595 5.35606 14.3988 4.07383 13.569 3.44415Z"
                                    fill="url(#paint2_linear_353_14160)" fill-opacity="0.2" />
                                <path
                                    d="M11.1188 16.6318L5.96777 15.0692L16.9194 8.12714C17.8417 7.54249 17.8393 6.19597 16.9149 5.61463L16.8656 5.58362L17.0076 5.67198L20.3407 7.83406C20.7506 8.09997 20.998 8.55536 20.998 9.04398V10.3427L11.1188 16.6318Z"
                                    fill="url(#paint3_linear_353_14160)" />
                                <path
                                    d="M11.1188 16.6318L5.96777 15.0692L16.9194 8.12714C17.8417 7.54249 17.8393 6.19597 16.9149 5.61463L16.8656 5.58362L17.0076 5.67198L20.3407 7.83406C20.7506 8.09997 20.998 8.55536 20.998 9.04398V10.3427L11.1188 16.6318Z"
                                    fill="url(#paint4_linear_353_14160)" fill-opacity="0.2" />
                                <path
                                    d="M13.6716 3.50781C12.655 2.84838 11.3457 2.84848 10.3292 3.50807L3.65728 7.83735C3.24745 8.10328 3.00012 8.55861 3.00012 9.04715V9.11278C3.01621 9.60317 3.27429 10.0552 3.69113 10.318L11.987 15.5481L20.3038 10.3259C20.7357 10.0547 20.9978 9.58056 20.9978 9.07055V10.3428L20.998 9.04398C20.998 8.55536 20.7506 8.09997 20.3407 7.83406L13.6716 3.50781Z"
                                    fill="url(#paint5_radial_353_14160)" />
                                <path
                                    d="M10.5247 21.0004H17.9799C19.6465 21.0004 20.9975 19.6493 20.9975 17.9827V9.07224C20.997 9.58161 20.7349 10.0551 20.3034 10.326L9.3739 17.1887C8.78434 17.5589 8.4265 18.2062 8.42656 18.9024C8.42665 20.0611 9.36602 21.0004 10.5247 21.0004Z"
                                    fill="url(#paint6_linear_353_14160)" />
                                <path
                                    d="M10.5247 21.0004H17.9799C19.6465 21.0004 20.9975 19.6493 20.9975 17.9827V9.07224C20.997 9.58161 20.7349 10.0551 20.3034 10.326L9.3739 17.1887C8.78434 17.5589 8.4265 18.2062 8.42656 18.9024C8.42665 20.0611 9.36602 21.0004 10.5247 21.0004Z"
                                    fill="url(#paint7_radial_353_14160)" fill-opacity="0.4" />
                                <path
                                    d="M10.5247 21.0004H17.9799C19.6465 21.0004 20.9975 19.6493 20.9975 17.9827V9.07224C20.997 9.58161 20.7349 10.0551 20.3034 10.326L9.3739 17.1887C8.78434 17.5589 8.4265 18.2062 8.42656 18.9024C8.42665 20.0611 9.36602 21.0004 10.5247 21.0004Z"
                                    fill="url(#paint8_radial_353_14160)" fill-opacity="0.5" />
                                <path
                                    d="M13.5122 20.9997H6.0166C4.35 20.9997 2.99896 19.6487 2.99896 17.9821V9.06406C2.99896 9.5731 3.26015 10.0465 3.69076 10.318L14.6095 17.2017C15.2072 17.5785 15.5698 18.2357 15.5697 18.9424C15.5696 20.0786 14.6484 20.9997 13.5122 20.9997Z"
                                    fill="url(#paint9_radial_353_14160)" />
                                <path
                                    d="M13.5122 20.9997H6.0166C4.35 20.9997 2.99896 19.6487 2.99896 17.9821V9.06406C2.99896 9.5731 3.26015 10.0465 3.69076 10.318L14.6095 17.2017C15.2072 17.5785 15.5698 18.2357 15.5697 18.9424C15.5696 20.0786 14.6484 20.9997 13.5122 20.9997Z"
                                    fill="url(#paint10_linear_353_14160)" />
                            </g>
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint11_radial_353_14160)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint12_radial_353_14160)" fill-opacity="0.5" />
                            <path
                                d="M6.47558 17.0837C5.75508 17.0837 5.16356 16.8512 4.70104 16.3864C4.23852 15.9216 4.00726 15.3149 4.00726 14.5665C4.00726 13.7763 4.24201 13.1371 4.7115 12.6491C5.18099 12.161 5.79575 11.9169 6.55577 11.9169C7.27395 11.9169 7.85849 12.1505 8.30939 12.6177C8.76261 13.0849 8.98923 13.7008 8.98923 14.4654C8.98923 15.251 8.75448 15.8844 8.28499 16.3655C7.81782 16.8443 7.21468 17.0837 6.47558 17.0837ZM6.4965 16.097C6.88929 16.097 7.20539 15.9587 7.44478 15.6822C7.68418 15.4056 7.80387 15.0209 7.80387 14.5282C7.80387 14.0145 7.68766 13.6148 7.45524 13.3289C7.22282 13.043 6.91254 12.9001 6.52439 12.9001C6.12463 12.9001 5.80272 13.0477 5.55868 13.3428C5.31464 13.6357 5.19261 14.0238 5.19261 14.5073C5.19261 14.9977 5.31464 15.3858 5.55868 15.6717C5.80272 15.9553 6.11533 16.097 6.4965 16.097Z"
                                fill="white" />
                            <defs>
                                <linearGradient id="paint0_linear_353_14160" x1="4.99373" y1="11.1815" x2="15.4647"
                                    y2="4.6869" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#20A7FA" />
                                    <stop offset="0.4" stop-color="#3BD5FF" />
                                    <stop offset="1" stop-color="#C4B0FF" />
                                </linearGradient>
                                <linearGradient id="paint1_linear_353_14160" x1="8.59779" y1="13.3962" x2="14.427"
                                    y2="4.06235" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#165AD9" />
                                    <stop offset="0.5008" stop-color="#1880E5" />
                                    <stop offset="1" stop-color="#8587FF" />
                                </linearGradient>
                                <linearGradient id="paint2_linear_353_14160" x1="12.8492" y1="13.5232" x2="6.37748"
                                    y2="8.24989" gradientUnits="userSpaceOnUse">
                                    <stop offset="0.236946" stop-color="#448AFF" stop-opacity="0" />
                                    <stop offset="0.792113" stop-color="#0032B1" />
                                </linearGradient>
                                <linearGradient id="paint3_linear_353_14160" x1="12.0253" y1="15.5539" x2="22.253"
                                    y2="9.00811" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#1A43A6" />
                                    <stop offset="0.492267" stop-color="#2052CB" />
                                    <stop offset="1" stop-color="#5F20CB" />
                                </linearGradient>
                                <linearGradient id="paint4_linear_353_14160" x1="14.9125" y1="15.1626" x2="8.69755"
                                    y2="9.78458" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#0045B9" stop-opacity="0" />
                                    <stop offset="0.669859" stop-color="#0D1F69" />
                                </linearGradient>
                                <radialGradient id="paint5_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(11.9997 3.40843) rotate(-90) scale(13.5006 14.6123)">
                                    <stop offset="0.568182" stop-color="#275FF0" stop-opacity="0" />
                                    <stop offset="0.992424" stop-color="#002177" />
                                </radialGradient>
                                <linearGradient id="paint6_linear_353_14160" x1="20.9975" y1="14.9705" x2="11.9248"
                                    y2="14.9705" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#4DC4FF" />
                                    <stop offset="0.196145" stop-color="#0FAFFF" />
                                </linearGradient>
                                <radialGradient id="paint7_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(14.0453 18.9546) rotate(-45) scale(5.78567)">
                                    <stop offset="0.259477" stop-color="#0060D1" />
                                    <stop offset="0.908166" stop-color="#0383F1" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint8_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(5.31507 23.2345) rotate(-52.6577) scale(19.6397 17.7592)">
                                    <stop offset="0.732317" stop-color="#F4A7F7" stop-opacity="0" />
                                    <stop offset="1" stop-color="#F4A7F7" />
                                </radialGradient>
                                <radialGradient id="paint9_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(9.28439 13.7649) rotate(123.339) scale(10.3623 26.8915)">
                                    <stop stop-color="#49DEFF" />
                                    <stop offset="0.724349" stop-color="#29C3FF" />
                                </radialGradient>
                                <linearGradient id="paint10_linear_353_14160" x1="1.72819" y1="18.9348" x2="10.4635"
                                    y2="18.9285" gradientUnits="userSpaceOnUse">
                                    <stop offset="0.205882" stop-color="#6CE0FF" />
                                    <stop offset="0.535" stop-color="#50D5FF" stop-opacity="0" />
                                </linearGradient>
                                <radialGradient id="paint11_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(1.96789 10.3462) rotate(46.9242) scale(11.8473)">
                                    <stop offset="0.038877" stop-color="#0091FF" />
                                    <stop offset="0.919119" stop-color="#183DAD" />
                                </radialGradient>
                                <radialGradient id="paint12_radial_353_14160" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(6.5 15.481) rotate(90) scale(6.3 7.26685)">
                                    <stop offset="0.557796" stop-color="#0FA5F7" stop-opacity="0" />
                                    <stop offset="1" stop-color="#74C6FF" />
                                </radialGradient>
                            </defs>
                        </svg>
                    </a>

                    {{-- Teams --}}
                    <a href="https://teams.microsoft.com" target="_blank" title="Teams"
                        class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M11.0001 10H17.1088C18.7056 10 20.0001 11.2945 20.0001 12.8913V18C20.0001 19.6569 18.657 21 17.0001 21C15.3433 21 14.0001 19.6569 14.0001 18V12.8913C14.0001 11.2945 12.7056 10 11.1088 10H11.0001Z"
                                fill="url(#paint0_radial_353_14793)" />
                            <path
                                d="M3.99988 11.8913C3.99988 10.2945 5.29436 9 6.89118 9H11.1086C12.7054 9 13.9999 10.2945 13.9999 11.8913V18C13.9999 19.6569 15.343 21 16.9999 21H8.95638C6.21898 21 3.99988 18.7809 3.99988 16.0435V11.8913Z"
                                fill="url(#paint1_radial_353_14793)" />
                            <path
                                d="M3.99988 11.8913C3.99988 10.2945 5.29436 9 6.89118 9H11.1086C12.7054 9 13.9999 10.2945 13.9999 11.8913V18C13.9999 19.6569 15.343 21 16.9999 21H8.95638C6.21898 21 3.99988 18.7809 3.99988 16.0435V11.8913Z"
                                fill="url(#paint2_linear_353_14793)" fill-opacity="0.7" />
                            <path
                                d="M3.99988 11.8913C3.99988 10.2945 5.29436 9 6.89118 9H11.1086C12.7054 9 13.9999 10.2945 13.9999 11.8913V18C13.9999 19.6569 15.343 21 16.9999 21H8.95638C6.21898 21 3.99988 18.7809 3.99988 16.0435V11.8913Z"
                                fill="url(#paint3_radial_353_14793)" fill-opacity="0.7" />
                            <path
                                d="M16.5 9C17.8807 9 19 7.88071 19 6.5C19 5.11929 17.8807 4 16.5 4C15.1193 4 14 5.11929 14 6.5C14 7.88071 15.1193 9 16.5 9Z"
                                fill="url(#paint4_radial_353_14793)" />
                            <path
                                d="M16.5 9C17.8807 9 19 7.88071 19 6.5C19 5.11929 17.8807 4 16.5 4C15.1193 4 14 5.11929 14 6.5C14 7.88071 15.1193 9 16.5 9Z"
                                fill="url(#paint5_radial_353_14793)" fill-opacity="0.46" />
                            <path
                                d="M16.5 9C17.8807 9 19 7.88071 19 6.5C19 5.11929 17.8807 4 16.5 4C15.1193 4 14 5.11929 14 6.5C14 7.88071 15.1193 9 16.5 9Z"
                                fill="url(#paint6_radial_353_14793)" fill-opacity="0.4" />
                            <path
                                d="M9 8C10.6569 8 12 6.65685 12 5C12 3.34315 10.6569 2 9 2C7.34315 2 6 3.34315 6 5C6 6.65685 7.34315 8 9 8Z"
                                fill="url(#paint7_radial_353_14793)" />
                            <path
                                d="M9 8C10.6569 8 12 6.65685 12 5C12 3.34315 10.6569 2 9 2C7.34315 2 6 3.34315 6 5C6 6.65685 7.34315 8 9 8Z"
                                fill="url(#paint8_radial_353_14793)" fill-opacity="0.6" />
                            <path
                                d="M9 8C10.6569 8 12 6.65685 12 5C12 3.34315 10.6569 2 9 2C7.34315 2 6 3.34315 6 5C6 6.65685 7.34315 8 9 8Z"
                                fill="url(#paint9_radial_353_14793)" fill-opacity="0.5" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint10_radial_353_14793)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint11_radial_353_14793)" fill-opacity="0.7" />
                            <path d="M8.40615 12.917H7.06553V17H5.93438V12.917H4.59375V12H8.40615V12.917Z"
                                fill="white" />
                            <defs>
                                <radialGradient id="paint0_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(19.8985 11.087) scale(6.73918 16.6347)">
                                    <stop stop-color="#A98AFF" />
                                    <stop offset="0.14" stop-color="#8C75FF" />
                                    <stop offset="0.565" stop-color="#5F50E2" />
                                    <stop offset="0.9" stop-color="#3C2CB8" />
                                </radialGradient>
                                <radialGradient id="paint1_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(4.40613 8.2) rotate(68.1539) scale(16.376 16.5616)">
                                    <stop stop-color="#85C2FF" />
                                    <stop offset="0.69" stop-color="#7588FF" />
                                    <stop offset="1" stop-color="#6459FE" />
                                </radialGradient>
                                <linearGradient id="paint2_linear_353_14793" x1="10.2968" y1="9" x2="10.2968" y2="21"
                                    gradientUnits="userSpaceOnUse">
                                    <stop offset="0.801159" stop-color="#6864F6" stop-opacity="0" />
                                    <stop offset="1" stop-color="#5149DE" />
                                </linearGradient>
                                <radialGradient id="paint3_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(13.7499 8.6) rotate(113.326) scale(9.60929 7.71365)">
                                    <stop stop-color="#BD96FF" />
                                    <stop offset="0.686685" stop-color="#BD96FF" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint4_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(16.5 5.78571) rotate(-90) scale(5 6.31082)">
                                    <stop offset="0.268201" stop-color="#6868F7" />
                                    <stop offset="1" stop-color="#3923B1" />
                                </radialGradient>
                                <radialGradient id="paint5_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(14.4338 5.27202) rotate(40.0516) scale(3.57314 5.16813)">
                                    <stop offset="0.270711" stop-color="#A1D3FF" />
                                    <stop offset="0.813393" stop-color="#A1D3FF" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint6_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(18.4911 5.18429) rotate(-41.6581) scale(4.25637 10.4412)">
                                    <stop stop-color="#E3ACFD" />
                                    <stop offset="0.816041" stop-color="#9FA2FF" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint7_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(9 4.14286) rotate(-90) scale(6 7.57298)">
                                    <stop offset="0.268201" stop-color="#8282FF" />
                                    <stop offset="1" stop-color="#3923B1" />
                                </radialGradient>
                                <radialGradient id="paint8_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(6.52059 3.52642) rotate(40.0516) scale(4.28777 6.20175)">
                                    <stop offset="0.270711" stop-color="#A1D3FF" />
                                    <stop offset="0.813393" stop-color="#A1D3FF" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint9_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(11.3893 3.42115) rotate(-41.6581) scale(5.10765 12.5295)">
                                    <stop stop-color="#E3ACFD" />
                                    <stop offset="0.816041" stop-color="#9FA2FF" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint10_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(2 10) rotate(45) scale(12.7279)">
                                    <stop offset="0.046875" stop-color="#688EFF" />
                                    <stop offset="0.946875" stop-color="#230F94" />
                                </radialGradient>
                                <radialGradient id="paint11_radial_353_14793" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(6.5 15.4) rotate(90) scale(6.3 7.35199)">
                                    <stop offset="0.570647" stop-color="#6965F6" stop-opacity="0" />
                                    <stop offset="1" stop-color="#8F8FFF" />
                                </radialGradient>
                            </defs>
                        </svg>
                    </a>

                    {{-- Word --}}
                    <a href="https://word.cloud.microsoft" target="_blank" title="Word"
                        class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M5 16.545L12 8L19 13.0526V19.25C19 20.2165 18.2165 21 17.25 21H8C6.34315 21 5 19.6569 5 18V16.545Z"
                                fill="url(#paint0_radial_353_14585)" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H17.4444L19 7V13.25C19 14.2165 18.2165 15 17.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint1_linear_353_14585)" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H17.4444L19 7V13.25C19 14.2165 18.2165 15 17.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint2_radial_353_14585)" fill-opacity="0.6" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H17.4444L19 7V13.25C19 14.2165 18.2165 15 17.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint3_radial_353_14585)" fill-opacity="0.1" />
                            <path
                                d="M5 6C5 4.34315 6.34315 3 8 3H17.25C18.2165 3 19 3.7835 19 4.75V7.25C19 8.2165 18.2165 9 17.25 9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint4_linear_353_14585)" />
                            <path
                                d="M5 6C5 4.34315 6.34315 3 8 3H17.25C18.2165 3 19 3.7835 19 4.75V7.25C19 8.2165 18.2165 9 17.25 9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint5_radial_353_14585)" fill-opacity="0.8" />
                            <path
                                d="M5 16.545L12 8L20 13.0526V19.25C20 20.2165 19.2165 21 18.25 21H8C6.34315 21 5 19.6569 5 18V16.545Z"
                                fill="#1657F4" />
                            <path
                                d="M5 16.545L12 8L20 13.0526V19.25C20 20.2165 19.2165 21 18.25 21H8C6.34315 21 5 19.6569 5 18V16.545Z"
                                fill="url(#paint6_radial_353_14585)" fill-opacity="0.4" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H18.4444L20 7V13.25C20 14.2165 19.2165 15 18.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint7_linear_353_14585)" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H18.4444L20 7V13.25C20 14.2165 19.2165 15 18.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint8_radial_353_14585)" fill-opacity="0.5" />
                            <path
                                d="M5 10.5192C5 9.27659 6.00736 8.26923 7.25 8.26923H18.4444L20 7V13.25C20 14.2165 19.2165 15 18.25 15H8C6.34315 15 5 16.3431 5 18V10.5192Z"
                                fill="url(#paint9_radial_353_14585)" fill-opacity="0.3" />
                            <path
                                d="M5 6C5 4.34315 6.34315 3 8 3H18.25C19.2165 3 20 3.7835 20 4.75V7.25C20 8.2165 19.2165 9 18.25 9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint10_linear_353_14585)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint11_radial_353_14585)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint12_radial_353_14585)" fill-opacity="0.65" />
                            <path
                                d="M9.5 12L8.42306 16.9994L7.13577 17L6.5 14L5.83446 17H4.53488L3.5 12.0006H4.56146L5.2 15.3L5.83446 12.0006H7.13577L7.8 15.3L8.42306 12.0006L9.5 12Z"
                                fill="white" />
                            <defs>
                                <radialGradient id="paint0_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(18.5919 20.9997) scale(22.2009 10.0747)">
                                    <stop offset="0.180414" stop-color="#1657F4" />
                                    <stop offset="0.574542" stop-color="#0036C4" />
                                </radialGradient>
                                <linearGradient id="paint1_linear_353_14585" x1="5" y1="12.5" x2="15.7215" y2="12.5"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#66C0FF" />
                                    <stop offset="0.25606" stop-color="#0094F0" />
                                </linearGradient>
                                <radialGradient id="paint2_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(19 7.35789) rotate(131.577) scale(14.2262 34.8313)">
                                    <stop offset="0.140259" stop-color="#D471FF" />
                                    <stop offset="0.830508" stop-color="#509DF5" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint3_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(16.9518 14.6154) rotate(90) scale(9.30769 47.4352)">
                                    <stop offset="0.283333" stop-color="#4F006F" stop-opacity="0" />
                                    <stop offset="1" stop-color="#4F006F" />
                                </radialGradient>
                                <linearGradient id="paint4_linear_353_14585" x1="5" y1="7.5" x2="18.9967" y2="7.71579"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#9DEAFF" />
                                    <stop offset="0.201276" stop-color="#3BD5FF" />
                                </linearGradient>
                                <radialGradient id="paint5_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(19 3.29282) rotate(165.952) scale(13.8168 35.1874)">
                                    <stop offset="0.0606583" stop-color="#E4A7FE" />
                                    <stop offset="0.538869" stop-color="#E4A7FE" stop-opacity="0" />
                                </radialGradient>
                                <radialGradient id="paint6_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(16.3626 22.0408) rotate(0.481618) scale(24.2597 10.781)">
                                    <stop offset="0.124676" stop-color="#4F006F" stop-opacity="0" />
                                    <stop offset="0.59498" stop-color="#4F006F" />
                                </radialGradient>
                                <linearGradient id="paint7_linear_353_14585" x1="5" y1="11.6856" x2="18.9987"
                                    y2="11.819" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#5966F3" />
                                    <stop offset="0.34" stop-color="#257BF9" />
                                    <stop offset="1" stop-color="#24ABFF" />
                                </linearGradient>
                                <radialGradient id="paint8_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(16.9518 14.6154) rotate(90) scale(9.30769 47.4352)">
                                    <stop offset="0.283333" stop-color="#4F006F" stop-opacity="0" />
                                    <stop offset="1" stop-color="#4F006F" />
                                </radialGradient>
                                <radialGradient id="paint9_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(16.9518 14.6154) rotate(90) scale(9.71329 27.1704)">
                                    <stop offset="0.55" stop-color="#4F006F" stop-opacity="0" />
                                    <stop offset="1" stop-color="#4F006F" />
                                </radialGradient>
                                <linearGradient id="paint10_linear_353_14585" x1="19" y1="5.64045" x2="5" y2="5.64045"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#66D9FF" />
                                    <stop offset="0.26" stop-color="#48CEFF" />
                                    <stop offset="1" stop-color="#AD9EFF" />
                                </linearGradient>
                                <radialGradient id="paint11_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(2 10) rotate(45) scale(12.7279)">
                                    <stop offset="0.0811439" stop-color="#367AF2" />
                                    <stop offset="0.871875" stop-color="#001A8F" />
                                </radialGradient>
                                <radialGradient id="paint12_radial_353_14585" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(6.5 15.4) rotate(90) scale(6.3 7.1803)">
                                    <stop offset="0.586954" stop-color="#2763E5" stop-opacity="0" />
                                    <stop offset="0.973806" stop-color="#58AAFE" />
                                </radialGradient>
                            </defs>
                        </svg>
                    </a>

                    {{-- Excel --}}
                    <a href="https://excel.cloud.microsoft" target="_blank" title="Excel"
                        class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M5 9.25C5 8.00736 6.00736 7 7.25 7H20V19.5C20 20.3284 19.3284 21 18.5 21H8C6.34315 21 5 19.6569 5 18V9.25Z"
                                fill="url(#paint0_radial_353_13375)" />
                            <path
                                d="M5 9.25C5 8.00736 6.00736 7 7.25 7H20V19.5C20 20.3284 19.3284 21 18.5 21H8C6.34315 21 5 19.6569 5 18V9.25Z"
                                fill="url(#paint1_radial_353_13375)" fill-opacity="0.7" />
                            <path
                                d="M5 11.25C5 10.0074 6.00736 9 7.25 9H14.5C13.6716 9 13 9.67157 13 10.5V13.5C13 14.3284 12.3284 15 11.5 15H8C6.34315 15 5 16.3431 5 18V11.25Z"
                                fill="url(#paint2_linear_353_13375)" />
                            <path
                                d="M5 11.25C5 10.0074 6.00736 9 7.25 9H14.5C13.6716 9 13 9.67157 13 10.5V13.5C13 14.3284 12.3284 15 11.5 15H8C6.34315 15 5 16.3431 5 18V11.25Z"
                                fill="url(#paint3_linear_353_13375)" fill-opacity="0.3" />
                            <path d="M5 6C5 4.34315 6.34315 3 8 3H14V9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint4_linear_353_13375)" />
                            <path d="M5 6C5 4.34315 6.34315 3 8 3H14V9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint5_radial_353_13375)" />
                            <path d="M5 6C5 4.34315 6.34315 3 8 3H14V9H8C6.34315 9 5 10.3431 5 12V6Z"
                                fill="url(#paint6_linear_353_13375)" />
                            <path
                                d="M13.5 3H18.5C19.3284 3 20 3.67157 20 4.5V7.5C20 8.32843 19.3284 9 18.5 9H13.5C12.6716 9 12 8.32843 12 7.5V4.5C12 3.67157 12.6716 3 13.5 3Z"
                                fill="url(#paint7_radial_353_13375)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint8_radial_353_13375)" />
                            <path
                                d="M9 10H4C2.89543 10 2 10.8954 2 12V17C2 18.1046 2.89543 19 4 19H9C10.1046 19 11 18.1046 11 17V12C11 10.8954 10.1046 10 9 10Z"
                                fill="url(#paint9_radial_353_13375)" fill-opacity="0.3" />
                            <path
                                d="M8.8042 17H7.4531L6.60474 15.4066C6.57448 15.3508 6.5512 15.3066 6.53491 15.2741C6.52095 15.2392 6.50582 15.1997 6.48953 15.1555H6.47556C6.45462 15.2113 6.43483 15.2566 6.41621 15.2915C6.39759 15.3264 6.37548 15.3694 6.34988 15.4205L5.47009 17H4.1958L5.72495 14.4965L4.30054 12H5.63418L6.38828 13.4226C6.41854 13.4807 6.44414 13.5318 6.46509 13.576C6.48836 13.6179 6.51164 13.6678 6.53491 13.7259H6.54888C6.58146 13.6585 6.60706 13.6051 6.62568 13.5656C6.64663 13.526 6.67456 13.4737 6.70947 13.4086L7.4915 12H8.76231L7.31694 14.4582L8.8042 17Z"
                                fill="white" />
                            <defs>
                                <radialGradient id="paint0_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(20 22.1667) rotate(-134.554) scale(20.1924 15.6857)">
                                    <stop offset="0.0647233" stop-color="#379539" />
                                    <stop offset="0.422149" stop-color="#297C2D" />
                                    <stop offset="0.703448" stop-color="#15561C" />
                                </radialGradient>
                                <radialGradient id="paint1_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(9.58333 12.0556) rotate(-136.975) scale(8.19306 6.32748)">
                                    <stop stop-color="#073B10" />
                                    <stop offset="0.992234" stop-color="#084A13" stop-opacity="0" />
                                </radialGradient>
                                <linearGradient id="paint2_linear_353_13375" x1="5" y1="13.5" x2="12.2753" y2="13.5"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#52D17C" />
                                    <stop offset="0.328512" stop-color="#4AA647" />
                                </linearGradient>
                                <linearGradient id="paint3_linear_353_13375" x1="12.125" y1="9" x2="12.125" y2="15.2514"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#29852F" />
                                    <stop offset="0.499968" stop-color="#4AA647" stop-opacity="0" />
                                </linearGradient>
                                <linearGradient id="paint4_linear_353_13375" x1="5.33151" y1="10.2947" x2="13.6472"
                                    y2="3.08241" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#66D052" />
                                    <stop offset="1" stop-color="#85E972" />
                                </linearGradient>
                                <radialGradient id="paint5_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(13.676 6.83169) rotate(-180) scale(4.50941 9.547)">
                                    <stop offset="0.292495" stop-color="#4EB43B" />
                                    <stop offset="1" stop-color="#72CC61" stop-opacity="0" />
                                </radialGradient>
                                <linearGradient id="paint6_linear_353_13375" x1="9.05733" y1="7.19613" x2="5"
                                    y2="7.19613" gradientUnits="userSpaceOnUse">
                                    <stop offset="0.183694" stop-color="#C0E075" stop-opacity="0" />
                                    <stop offset="1" stop-color="#D1EB95" />
                                </linearGradient>
                                <radialGradient id="paint7_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(21.0192 9.8397) rotate(-142.825) scale(11.3193 11.1146)">
                                    <stop offset="0.439983" stop-color="#79E96D" />
                                    <stop offset="1" stop-color="#D0EB76" />
                                </radialGradient>
                                <radialGradient id="paint8_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(2 10) rotate(45) scale(12.7279 36.2329)">
                                    <stop stop-color="#20A85E" />
                                    <stop offset="0.94375" stop-color="#09442A" />
                                </radialGradient>
                                <radialGradient id="paint9_radial_353_13375" cx="0" cy="0" r="1"
                                    gradientUnits="userSpaceOnUse"
                                    gradientTransform="translate(6.5 15.4) rotate(90) scale(6.3 7.25625)">
                                    <stop offset="0.580357" stop-color="#33A662" stop-opacity="0" />
                                    <stop offset="0.973806" stop-color="#98F0B0" />
                                </radialGradient>
                            </defs>
                        </svg>
                    </a>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>