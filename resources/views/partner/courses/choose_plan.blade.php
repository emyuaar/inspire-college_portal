@extends('layouts.partner')

@section('title', 'Select Payment Plan')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-12">
        <div class="mb-8">
            <a href="{{ route('partner.learners.show', $isNewEnrolment ? $learner->id : $enrolment->learner_id) }}"
                class="text-slate-500 hover:text-indigo-600 flex items-center gap-2 text-sm font-bold transition-colors">
                <i class="fas fa-arrow-left"></i> Back to Learner Profile
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="px-8 py-8 border-b border-slate-50 bg-slate-50/50">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Finalize Enrollment</h1>
                <p class="text-slate-500 mt-2 font-medium">Course: <span class="text-indigo-600 font-bold">{{ $isNewEnrolment ? $course->title : $enrolment->course->title }}</span></p>
                
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Original Course Fee</span>
                        <span class="mt-1 block text-lg font-black text-slate-900">£{{ number_format((float) $quote['original_course_fee'], 2) }}</span>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <span class="block text-[10px] font-black text-emerald-600 uppercase tracking-widest">Partner Discount</span>
                        <span class="mt-1 block text-sm font-black text-emerald-800">
                            @if($quote['partner_discount_type'] === 'percentage')
                                {{ rtrim(rtrim($quote['partner_discount_value'], '0'), '.') }}%
                            @else
                                Fixed amount
                            @endif
                            (-£{{ number_format((float) $quote['partner_discount_amount'], 2) }})
                        </span>
                    </div>
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                        <span class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest">Discounted Course Fee</span>
                        <span class="mt-1 block text-lg font-black text-indigo-700">£{{ number_format((float) $quote['final_course_fee'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm font-bold flex items-center gap-3">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ $isNewEnrolment ? route('partner.enrolments.store_with_plan', [$learner->id, $course->id]) : route('partner.enrolments.update_plan', $enrolment->id) }}" method="POST" x-data="{ selectedPlan: '' }">
                    @csrf

                    <div class="mb-6">
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Select Payment Method</h3>
                        <p class="text-slate-500 text-sm font-medium mt-1">Choose one payment method to finalize the enrollment</p>
                    </div>

                    <div class="space-y-4">
                        @foreach($plans as $type => $plan)
                            @if($type == 'installment_unavailable')
                                <div class="p-6 border-2 border-dashed border-slate-100 rounded-3xl opacity-60">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-500">Custom Installments Unavailable</p>
                                            <p class="text-xs text-slate-400">No active plan has been configured for this course.</p>
                                        </div>
                                    </div>
                                </div>
                                @continue
                            @endif

                            <label class="relative block cursor-pointer group">
                                <input type="radio" name="plan_type" value="{{ $type }}" x-model="selectedPlan" class="peer hidden" required>
                                
                                <div class="p-6 border-2 rounded-3xl transition-all duration-200"
                                    :class="selectedPlan === '{{ $type }}' ? 'border-indigo-600 bg-indigo-50/50 shadow-lg shadow-indigo-100' : 'border-slate-100 bg-white group-hover:border-slate-200'">
                                    
                                    <div class="flex items-start justify-between gap-6">
                                        {{-- Left Section: Icon + Text --}}
                                        <div class="flex items-start gap-5 flex-1">
                                            <div class="shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-200"
                                                :class="selectedPlan === '{{ $type }}' ? '{{ $type == 'full' ? 'bg-emerald-600 text-white' : (in_array($type, ['two_months', 'three_months']) ? 'bg-blue-600 text-white' : 'bg-purple-600 text-white') }}' : '{{ $type == 'full' ? 'bg-emerald-50 text-emerald-600' : (in_array($type, ['two_months', 'three_months']) ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600') }}'">
                                                <i class="fas {{ $type == 'full' ? 'fa-check-double' : (in_array($type, ['two_months', 'three_months']) ? 'fa-calendar-alt' : 'fa-layer-group') }} text-xl"></i>
                                            </div>
                                            
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-black text-slate-900 transition-colors" :class="selectedPlan === '{{ $type }}' ? 'text-indigo-700' : ''">{{ $plan['title'] }}</h4>
                                                    <template x-if="selectedPlan === '{{ $type }}'">
                                                        <span class="bg-indigo-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">Selected</span>
                                                    </template>
                                                </div>
                                                <p class="text-sm text-slate-500 font-medium line-clamp-1">{{ $plan['description'] }}</p>
                                            </div>
                                        </div>

                                        {{-- Right Section: Amount + Radio Button --}}
                                        <div class="flex items-center gap-4 shrink-0">
                                            <div class="text-right">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Due Now</p>
                                                <p class="text-xl font-black text-slate-900">£{{ number_format($plan['amount'], 2) }}</p>
                                            </div>
                                            
                                            <div class="w-6 h-6 rounded-full border-2 transition-all flex items-center justify-center shrink-0"
                                                :class="selectedPlan === '{{ $type }}' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-200 bg-white group-hover:border-slate-300'">
                                                <template x-if="selectedPlan === '{{ $type }}'">
                                                    <i class="fas fa-check text-white text-[10px]"></i>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Plan Specific Details (Breakdowns) --}}
                                    <div class="mt-4 pl-16"> {{-- Offset by icon width + gap --}}
                                        @if(($plan['number_of_installments'] ?? 1) > 1)
                                            <div class="grid gap-3 {{ ($plan['number_of_installments'] ?? 1) === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3' }}">
                                                @foreach($plan['installments'] as $inst)
                                                    <div class="border border-slate-100 rounded-xl p-3 text-center transition-colors"
                                                        :class="selectedPlan === '{{ $type }}' ? 'bg-white border-indigo-100' : 'bg-slate-50/50'">
                                                        <p class="text-[9px] font-black text-slate-400 uppercase">{{ $inst['due'] }}</p>
                                                        <p class="text-sm font-bold text-slate-900 mt-1">£{{ number_format($inst['amount'], 2) }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="mt-3 text-right text-xs font-bold text-slate-500">
                                            Plan total: <span class="font-black text-slate-900">£{{ number_format((float) $plan['total_amount'], 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        <button type="submit" 
                            :disabled="!selectedPlan"
                            :class="!selectedPlan ? 'opacity-50 cursor-not-allowed bg-slate-300' : 'bg-slate-900 hover:bg-indigo-600 shadow-xl shadow-indigo-100'"
                            class="w-full text-white font-black py-5 rounded-2xl transition-all hover:-translate-y-1 active:translate-y-0 text-sm uppercase tracking-widest flex items-center justify-center gap-3">
                            
                            <template x-if="!selectedPlan">
                                <span>Select a Payment Method to Continue</span>
                            </template>
                            
                            <template x-if="selectedPlan === 'full'">
                                <span>Continue with Full Payment</span>
                            </template>
                            
                            <template x-if="selectedPlan === 'three_months'">
                                <span>Continue with 3 Months</span>
                            </template>

                            <template x-if="selectedPlan === 'two_months'">
                                <span>Continue with 2 Months</span>
                            </template>
                            
                            <template x-if="selectedPlan.startsWith('installment_')">
                                <span>Continue with Installment Plan</span>
                            </template>

                            <i class="fas fa-arrow-right text-xs" x-show="selectedPlan"></i>
                        </button>
                        
                        <p class="text-center text-[10px] text-slate-400 font-bold mt-6 uppercase tracking-widest flex items-center justify-center gap-2">
                            <i class="fas fa-shield-alt"></i> Secure Administrative Enrollment & Billing
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
