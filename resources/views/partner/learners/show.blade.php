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
            <h1 class="text-xl font-bold text-slate-900">{{ $learner->first_name }} {{ $learner->sur_name }}</h1>
            <p class="text-sm text-slate-500">Learner Profile & Enrolments</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LEFT COLUMN: PROFILE & STATUS --}}
        <div class="lg:col-span-5 space-y-6">
            
            <x-ui.card title="Learner Profile" subtitle="Account & Provisioning Status">
                <x-slot name="actions">
                     @if(!$learner->crm_approved)
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
                            <div class="font-bold text-slate-800">{{ $learner->first_name }} {{ $learner->sur_name }}</div>
                            <div class="text-slate-500">{{ $learner->email_address }}</div>
                        </div>
                    </div>

                    {{-- CRM Status --}}
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">CRM Approval</dt>
                        <dd class="text-right">
                             @if($learner->crm_approved)
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
                        <dt class="text-slate-500">Microsoft 365</dt>
                        <dd class="text-right">
                             @if($learner->ms_user_id)
                                 @if($learner->status_id == 2)
                                     <span class="font-bold text-emerald-600">Active</span>
                                 @else
                                     <span class="font-bold text-amber-600">Provisioned (Disabled)</span>
                                 @endif
                                 <div class="text-[10px] text-slate-400 font-mono mt-0.5" title="{{ $learner->ms_user_id }}">
                                     ID: {{ substr($learner->ms_user_id, 0, 8) }}...
                                 </div>
                             @else
                                 <span class="font-bold text-slate-400">Not Provisioned</span>
                             @endif
                        </dd>
                    </div>

                    {{-- Portal Access --}}
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Portal Access</dt>
                        <dd class="text-right">
                            @if($learner->status_id == 2)
                                <span class="font-bold text-slate-700 flex items-center justify-end gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Enabled
                                </span>
                            @else
                                <span class="font-bold text-slate-400">Disabled</span>
                            @endif
                        </dd>
                    </div>

                     {{-- Financial Rollup --}}
                    <div class="flex justify-between pt-3 border-t border-slate-50">
                        <dt class="text-slate-500">Financial Status</dt>
                        <dd class="text-right">
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
                                       $label = 'Action Required';
                                       $color = 'warning';
                                       break; 
                                   }
                                   if ($p['status'] === 'installments_active') {
                                       $overallStatus = 'installments';
                                       $label = 'Installments Active';
                                       $color = 'brand';
                                   }
                               }
                               if ($enrolments->isEmpty()) {
                                   $overallStatus = 'none';
                                   $label = 'No Enrolments';
                                   $color = 'neutral';
                               }
                           @endphp
                           <x-ui.badge variant="{{ $color }}" size="sm">{{ $label }}</x-ui.badge>
                        </dd>
                    </div>

                </dl>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN: COURSES --}}
        <div class="lg:col-span-7 space-y-6">
            <x-ui.card title="Enrolled Courses" subtitle="Manage payment plans and view progress">
                <x-slot name="actions">
                    @if($learner->crm_approved)
                        <x-ui.button variant="outline" size="sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <x-slot name="icon">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </x-slot>
                            Add Course
                        </x-ui.button>
                        @include('partner.courses.partials.add_modal')
                    @endif
                </x-slot>

                <div class="space-y-4 mt-2">
                    @forelse($enrolments as $enrolment)
                        @php
                            $payment = $enrolment->payment_status_details;
                            $statusName = strtolower($enrolment->status->status ?? 'Unknown');
                            
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
                                        {{ $enrolment->course->title ?? 'Unknown Course' }}
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
                                    
                                    @if($statusName == 'pending-payment' && $learner->crm_approved)
                                        <div class="mt-2">
                                           <form action="{{ route('partner.checkout', $learner->id) }}" method="POST" 
                                              x-data="{ 
                                                expanded: false, 
                                                code: '', 
                                                status: 'idle', 
                                                msg: '',
                                                validate() {
                                                    if(!this.code) return;
                                                    this.status = 'loading';
                                                    fetch('{{ route('partner.coupon.validate') }}', {
                                                        method: 'POST',
                                                        headers: { 
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                                                        },
                                                        body: JSON.stringify({ code: this.code })
                                                    })
                                                    .then(r => r.json())
                                                    .then(d => {
                                                        this.status = d.valid ? 'valid' : 'invalid';
                                                        this.msg = d.message;
                                                    })
                                                    .catch(() => {
                                                        this.status = 'invalid';
                                                        this.msg = 'System error';
                                                    });
                                                }
                                            }">
                                            @csrf
                                            <input type="hidden" name="enrolment_id" value="{{ $enrolment->id }}">
                                            
                                            <div class="flex flex-col items-end gap-2 mb-2">
                                                 <!-- Trigger -->
                                                 <button type="button" 
                                                    x-show="!expanded" 
                                                    @click="expanded = true" 
                                                    class="text-xs text-slate-500 hover:text-brand-600 underline">
                                                    Add Coupon?
                                                 </button>
                                        
                                                 <!-- Input Area -->
                                                 <div x-show="expanded" class="flex flex-col items-end gap-1" x-cloak x-transition>
                                                     <div class="flex items-center gap-1">
                                                         <input type="text" name="coupon_code" x-model="code" 
                                                            class="text-xs border-slate-300 rounded focus:ring-brand-500 focus:border-brand-500 w-32 py-1 px-2" 
                                                            placeholder="Enter code">
                                                         <button type="button" @click="validate()" 
                                                            class="bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 text-xs py-1 px-2 rounded transition-colors"
                                                            :disabled="status === 'loading'">
                                                            Apply
                                                         </button>
                                                     </div>
                                                     <div x-show="msg" x-text="msg" class="text-[10px] font-medium"
                                                        :class="status === 'valid' ? 'text-emerald-600' : 'text-rose-600'"></div>
                                                 </div>
                                            </div>

                                            <x-ui.button variant="primary" size="sm" type="submit" class="w-full justify-center">
                                                Pay Now
                                            </x-ui.button>
                                        </form>
                                    </div>

                                    @elseif($statusName == 'pending')
                                         @php
                                            $onboarding = \App\Models\Crm\LearnerOnboardingStatus::where('learner_id', $learner->id)->first();
                                            $reqMet = $onboarding && 
                                                       $onboarding->personal_info_completed && 
                                                       $onboarding->rpl_info_completed && 
                                                       $onboarding->disability_info_completed;
                                         @endphp

                                         @if(!$reqMet)
                                             <span class="text-xs font-bold text-amber-600 block text-right">Requirements Pending</span>
                                             <span class="text-[10px] text-slate-400 block text-right">Learner must fill forms</span>
                                         @elseif(!$learner->crm_approved)
                                             <span class="text-xs font-medium text-slate-400 italic">Awaiting Admin Review</span>
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
