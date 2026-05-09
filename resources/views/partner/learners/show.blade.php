@extends('layouts.partner')

@section('title', 'Learner Details')
@section('active-page', 'learners')

@section('content')
<div class="space-y-6">

    {{-- HEADER WITH BACK BUTTON --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('partner.learners.index') }}" class="group flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-ds-navy hover:border-ds-navy transition-all shadow-sm">
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $learner->first_name }} {{ $learner->sur_name ?? $learner->last_name }}</h1>
            <p class="text-sm text-slate-500">Learner Profile & Enrolments</p>
        </div>
    </div>
    
    @php
        $hasEnrolment = $enrolments->isNotEmpty();
        $hasAwaitingProof = false;
        $hasPendingPayment = false;
        $welcomeSent = false;
        $passwordSet = ($learner instanceof \App\Models\Crm\PartnerLearner && $learner->activation_status === 'completed');
        $courseName = $hasEnrolment ? (optional($enrolments->first()->course)->title ?? 'a course') : null;
        
        foreach($enrolments as $e) {
            $p = $e->payment_status_details;
            if (isset($p['status']) && $p['status'] === 'awaiting_approval') $hasAwaitingProof = true;
            if (isset($p['status']) && $p['status'] === 'pending_payment') $hasPendingPayment = true;
            if ($e->welcome_email_sent_at) $welcomeSent = true;
        }

        // Determine State
        $state = 'add_course';
        if ($hasEnrolment) $state = 'payment';
        if ($hasAwaitingProof) $state = 'review';
        if ($welcomeSent) $state = 'password_sent';
        if ($passwordSet) $state = 'completed';
    @endphp

    @if($isPending)
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-6">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex-1">
                {{-- Dynamic Heading --}}
                <h2 class="text-lg font-bold text-blue-900 mb-1">
                    @if($state == 'add_course')
                        Next Step: Add a Course
                    @elseif($state == 'payment')
                        Next Step: Complete Payment
                    @elseif($state == 'review')
                        Payment Proof Under Review
                    @elseif($state == 'password_sent')
                        Password Setup Email Sent
                    @else
                        Account Fully Activated
                    @endif
                </h2>

                {{-- Dynamic Text --}}
                <p class="text-sm text-blue-700 max-w-2xl">
                    @if($state == 'add_course')
                        The learner profile is created, but access is not active yet. Add a course, complete the first payment, and the learner will receive a secure password setup email.
                    @elseif($state == 'payment')
                        This learner is enrolled on <strong>{{ $courseName }}</strong>. Please complete the first payment by Stripe or upload bank transfer proof. Once payment is confirmed, the learner will receive a secure password setup email.
                    @elseif($state == 'review')
                        We’ve received the payment proof. The learner account will activate automatically once our team approves it.
                    @elseif($state == 'password_sent')
                        The first payment has been confirmed and this learner account has been activated. A secure “Set Your Password” email has been sent to the learner. Once the learner sets their password, they can log in and access the course.
                    @else
                        The learner has successfully set their password and now has full access to the portal and their enrolled courses.
                    @endif
                </p>

                {{-- Compact Progress Line --}}
                <div class="mt-4 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider">
                    <span class="{{ $state == 'add_course' ? 'text-blue-600' : 'text-emerald-600' }}">1. Add Course ✓</span>
                    <svg class="w-3 h-3 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="{{ $state == 'payment' ? 'text-blue-600' : 'text-emerald-600' }}">2. Payment Confirmed ✓</span>
                    <svg class="w-3 h-3 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="{{ $state == 'completed' ? 'text-emerald-600' : ($state == 'password_sent' ? 'text-blue-600' : 'text-slate-400') }}">3. Learner Sets Password {{ $state == 'completed' ? '✓' : '' }}</span>
                </div>
            </div>

            </div>
        </div>
    </div>
    @endif
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LEFT COLUMN: PROFILE & STATUS --}}
        <div class="lg:col-span-5 space-y-6" id="profile-details">
            
            <x-ui.card title="Learner Profile" subtitle="Account & Provisioning Status">
                <x-slot name="actions">
                     @if($passwordSet)
                        <x-ui.badge variant="success">Active</x-ui.badge>
                     @elseif($isPending)
                        <x-ui.badge variant="warning">Activation Pending</x-ui.badge>
                     @elseif(!$learner->crm_approved)
                        <x-ui.badge variant="warning">Pending Approval</x-ui.badge>
                    @elseif($learner->status_id == 2)
                         <x-ui.badge variant="success">Active</x-ui.badge>
                    @else
                        <x-ui.badge variant="brand">Approved</x-ui.badge>
                    @endif
                </x-slot>

                 <dl class="space-y-4 text-sm mt-2">
                    {{-- Avatar & Email --}}
                    <div class="flex items-center gap-4 border-b border-slate-50 pb-4">
                        <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-lg border border-slate-200">
                             {{ substr($learner->first_name, 0, 1) }}{{ substr($learner->sur_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-800">{{ $learner->first_name }} {{ $learner->sur_name ?? $learner->last_name }}</div>
                            <div class="text-slate-500">{{ $learner->email_address ?? $learner->personal_email }}</div>
                        </div>
                    </div>

                     {{-- Admissions Status --}}
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Admissions Approval</dt>
                        <dd class="text-right">
                             @if($welcomeSent)
                                <span class="font-bold text-emerald-600 flex items-center justify-end gap-1">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Activated
                                </span>
                             @elseif($isPending)
                                <span class="font-bold text-slate-400">Awaiting Activation</span>
                             @elseif($learner->crm_approved)
                                <span class="font-bold text-slate-700 flex items-center justify-end gap-1">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Approved
                                </span>
                             @else
                                <span class="font-bold text-amber-600">Pending Review</span>
                             @endif
                        </dd>
                    </div>

                    {{-- MS 365 Status --}}
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Course Access</dt>
                        <dd class="text-right">
                             @if($passwordSet)
                                <span class="font-bold text-emerald-600">Active</span>
                             @elseif($welcomeSent)
                                <span class="font-bold text-blue-600">Available after password setup</span>
                             @elseif(!$isPending && $learner->ms_user_id)
                                  @if($learner->status_id == 2)
                                      <span class="font-bold text-emerald-600">Available</span>
                                  @else
                                      <span class="font-bold text-amber-600">Provisioned (Disabled)</span>
                                  @endif
                             @else
                                  <span class="font-bold text-slate-400 italic">Not available yet</span>
                             @endif
                        </dd>
                    </div>

                    {{-- Password Email Status --}}
                    @if($isPending)
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Password Setup Email</dt>
                        <dd class="text-right font-bold {{ $welcomeSent ? 'text-emerald-600' : 'text-slate-400 italic' }}">
                            {{ $welcomeSent ? 'Sent' : 'Not sent yet' }}
                        </dd>
                    </div>
                    @endif

                    {{-- Portal Access --}}
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Portal Access</dt>
                        <dd class="text-right">
                             @if($passwordSet)
                                <span class="font-bold text-emerald-600">Active</span>
                             @elseif($welcomeSent)
                                <span class="font-bold text-blue-600">Waiting for learner password setup</span>
                             @elseif(!$isPending && $learner->status_id == 2)
                                <span class="font-bold text-slate-700 flex items-center justify-end gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Enabled
                                </span>
                             @else
                                <span class="font-bold text-slate-400 italic">Disabled until course payment is confirmed</span>
                             @endif
                        </dd>
                    </div>

                     {{-- Financial Rollup --}}
                    <div class="flex justify-between pt-3 border-t border-slate-50">
                        <dt class="text-slate-500">{{ $enrolments->isEmpty() ? 'Course Status' : 'Financial Status' }}</dt>
                        <dd class="text-right">
                            @if($enrolments->isEmpty())
                                <x-ui.badge variant="neutral" size="sm">No Course Added</x-ui.badge>
                            @else
                                @php
                                   $overallStatus = 'paid';
                                   $label = 'All Settled';
                                   $color = 'success'; // badge variant
    
                                   foreach($enrolments as $e) {
                                       $p = $e->payment_status_details; 
                                       if ($e->status_id == 5) { // Pending Plan
                                           $overallStatus = 'review';
                                           $label = 'Plan Review Pending';
                                           $color = 'brand';
                                           break;
                                       }
                                       if ($p['status'] === 'pending_payment') {
                                           $overallStatus = 'pending';
                                           $label = 'Payment Pending';
                                           $color = 'warning';
                                           break; 
                                       }
                                       if ($p['status'] === 'installments_active') {
                                           $overallStatus = 'installments';
                                           $label = 'Installments Active';
                                           $color = 'brand';
                                       }
                                   }
                               @endphp
                               <x-ui.badge variant="{{ $color }}" size="sm">{{ $label }}</x-ui.badge>
                           @endif
                        </dd>
                    </div>

                </dl>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN: COURSES --}}
        <div class="lg:col-span-7 space-y-6" id="enrolled-courses">
            <x-ui.card title="Enrolled Courses" subtitle="Manage payment plans and view progress">
                <x-slot name="actions">
                    <x-ui.button variant="outline" size="sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <x-slot name="icon">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </x-slot>
                            Add Course
                        </x-ui.button>
                        @include('partner.courses.partials.add_modal')
                </x-slot>

                <div class="space-y-4 mt-2">
                    @forelse($enrolments as $enrolment)
                        @php
                            $payment = $enrolment->payment_status_details;
                            $statusName = strtolower(optional($enrolment->status)->status ?? 'Unknown');
                            
                            // Badge Variant Logic
                            $statusVariant = match($statusName) {
                                'active', 'approved', 'paid' => 'success',
                                'pending-payment' => 'warning',
                                'pending-plan' => 'brand', // blue
                                'denied' => 'error',
                                default => 'neutral'
                            };

                            // Payment Badge Variant logic
                             $paymentVariant = match($payment['color']) {
                                'emerald' => 'success',
                                'amber' => 'warning',
                                'blue' => 'brand',
                                default => 'neutral'
                            };
                        @endphp

                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-slate-200 hover:shadow-sm">
                            
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        {{-- Enrolment Status --}}
                                        <x-ui.badge variant="{{ $statusVariant }}" size="sm">
                                            {{ str_replace('-', ' ', ucfirst($statusName)) }}
                                        </x-ui.badge>

                                        {{-- Payment Status --}}
                                        <x-ui.badge variant="{{ $paymentVariant }}" size="sm">
                                            {{ $payment['label'] }}
                                        </x-ui.badge>
                                    </div>

                                    <h3 class="font-bold text-slate-800 text-sm md:text-base">
                                        {{ optional($enrolment->course)->title ?? 'Unknown Course' }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Enrolled on {{ $enrolment->created_at->format('M d, Y') }}</p>
                                </div>
                                
                                <div class="shrink-0 pt-1">
                                    {{-- Actions --}}
                                    @php 
                                       // Only allow plan selection if no plan was previously chosen/locked-in
                                       $selectedPlan = $enrolment->orders()->exists() || \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $enrolment->id)->exists();
                                       $canReview = in_array($statusName, ['pending-plan', 'pending']) && !$selectedPlan;
                                    @endphp

                                    @if($canReview && $learner->crm_approved)
                                        <div class="flex flex-col gap-2">
                                            @if($statusName == 'pending-payment')
                                                <x-ui.button variant="outline" size="sm" href="{{ route('partner.enrolments.choose_plan', $enrolment->id) }}">
                                                    Review Plan
                                                </x-ui.button>
                                            @else
                                                <x-ui.button variant="brand" size="sm" href="{{ route('partner.enrolments.choose_plan', $enrolment->id) }}">
                                                    Review Plan
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($statusName == 'pending-payment')
                                        <div class="mt-2 space-y-2">
                                            @php
                                                $pendingInstallments = $enrolment->partnerInstallments()->where('status', '!=', 'paid')->get();
                                                $hasAwaitingProof = $pendingInstallments->whereIn('status', ['awaiting_approval', 'proof_submitted'])->isNotEmpty();
                                                
                                                // Find the specific installment to pay/record (usually the oldest unpaid)
                                                $currentInst = $pendingInstallments->sortBy('due_date')->first();
                                                $hasRejectedProof = $currentInst && $currentInst->status === 'rejected';
                                            @endphp

                                            @if($hasAwaitingProof)
                                                <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-center">
                                                    <div class="flex items-center justify-center gap-2 mb-1">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span class="text-xs font-bold text-blue-800">Payment Proof Submitted</span>
                                                    </div>
                                                    <span class="block text-[10px] text-blue-600 uppercase tracking-tight font-medium">Awaiting Admin Approval</span>
                                                </div>
                                            @elseif($currentInst)
                                                @if($hasRejectedProof)
                                                    <div class="p-2 bg-red-50 border border-red-100 rounded-lg mb-2">
                                                        <div class="flex items-center gap-1.5 text-red-700 mb-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            <span class="text-[10px] font-bold uppercase">Proof Rejected</span>
                                                        </div>
                                                        @if($currentInst->rejection_reason)
                                                            <p class="text-[10px] text-red-600 leading-tight italic">"{{ $currentInst->rejection_reason }}"</p>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Option 1: Stripe (if enabled/available) --}}
                                                <form action="{{ route('partner.checkout', $isPending ? 'pending-'.$learner->id : $learner->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="enrolment_id" value="{{ $enrolment->id }}">
                                                    <x-ui.button variant="primary" size="sm" type="submit" class="w-full justify-center">
                                                        <x-slot name="icon">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                        </x-slot>
                                                        Pay via Stripe
                                                    </x-ui.button>
                                                </form>

                                                <div class="relative flex py-1 items-center">
                                                    <div class="flex-grow border-t border-slate-200"></div>
                                                    <span class="flex-shrink mx-2 text-[10px] text-slate-400 font-bold uppercase">OR</span>
                                                    <div class="flex-grow border-t border-slate-200"></div>
                                                </div>

                                                {{-- Option 2: Bank Transfer (Upload Proof) --}}
                                                {{-- Re-using the installment modal from the partial --}}
                                                <x-ui.button variant="outline" size="sm" class="w-full justify-center" data-bs-toggle="modal" data-bs-target="#payModal-{{ $currentInst->id }}">
                                                    <x-slot name="icon">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    </x-slot>
                                                    {{ $hasRejectedProof ? 'Re-upload Proof' : 'Upload Proof' }}
                                                </x-ui.button>
                                            @endif
                                        </div>
                                     @elseif($statusName == 'pending' || ($isPending && $statusName == 'pending_payment'))
                                         @php
                                            $onboarding = !$isPending ? \App\Models\Crm\LearnerOnboardingStatus::where('learner_id', $learner->id)->first() : null;
                                            $reqMet = $onboarding && 
                                                       $onboarding->personal_info_completed && 
                                                       $onboarding->rpl_info_completed && 
                                                       $onboarding->disability_info_completed;
                                         @endphp
 
                                         @if($isPending)
                                             <span class="text-xs font-bold text-amber-600 block text-right">Payment Pending</span>
                                             <span class="text-[10px] text-slate-400 block text-right">Activation follows payment</span>
                                         @elseif(!$reqMet)
                                             <span class="text-xs font-bold text-amber-600 block text-right">Requirements Pending</span>
                                             <span class="text-[10px] text-slate-400 block text-right">Learner must fill forms</span>
                                         @elseif(!$learner->crm_approved)
                                             <span class="text-xs font-medium text-slate-400 italic">Awaiting Admissions Review</span>
                                         @else
                                             <span class="text-xs font-medium text-slate-400">Processing...</span>
                                         @endif
                                    @endif
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <p class="text-sm">No courses enrolled yet.</p>
                        </div>
                    @endforelse
                </div>

            </x-ui.card>
        </div>
    </div>

    {{-- Installments Section (Full Width) --}}
    @include('partner.learners.partials.installments')

</div>
@endsection
