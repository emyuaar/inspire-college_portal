@extends('layouts.partner')

@section('title', 'Select Payment Plan')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('partner.learners.show', $enrolment->learner_id) }}" class="text-slate-500 hover:text-slate-700 flex items-center gap-2 text-sm">
            &larr; Back to Learner
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <h1 class="text-lg font-bold text-slate-900">Select Payment Plan</h1>
            <p class="text-sm text-slate-500 mt-1">Course: <span class="font-semibold">{{ $enrolment->course->title }}</span></p>
        </div>

        <div class="p-6">
            @if(session('error'))
                <div class="mb-4 p-4 rounded bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
            @endif

            <form action="{{ route('partner.enrolments.update_plan', $enrolment->id) }}" method="POST">
                @csrf
                
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Choose how you want to pay:</h3>

                <div class="grid grid-cols-1 gap-4">
                    {{-- Full Payment --}}
                    <label class="relative border border-slate-200 rounded-xl p-5 cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/10 transition-all group">
                        <div class="flex items-start gap-4">
                            <div class="pt-1">
                                <input type="radio" name="plan_type" value="full" checked class="h-5 w-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-slate-900">Full Payment</span>
                                    <span class="text-lg font-bold text-indigo-600">£{{ $pricing->final_full_price }}</span>
                                </div>
                                <p class="text-sm text-slate-500">Pay the entire course fee upfront.</p>
                                @if($pricing->is_promo)
                                    <div class="mt-2 inline-flex items-center px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">
                                        Includes {{ $pricing->discount_percent }}% Discount
                                    </div>
                                @endif
                            </div>
                        </div>
                    </label>

                    {{-- Installment Plan --}}
                    @if($pricing->installment_plan['available'])
                        <label class="relative border border-slate-200 rounded-xl p-5 cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/10 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="pt-1">
                                    <input type="radio" name="plan_type" value="installment" class="h-5 w-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-slate-900">Installment Plan</span>
                                        <span class="text-lg font-bold text-slate-900">£{{ $pricing->installment_plan['deposit'] }} <span class="text-sm font-normal text-slate-500">deposit</span></span>
                                    </div>
                                    <p class="text-sm text-slate-500 mb-2">Pay a deposit now, then monthly installments.</p>
                                    
                                    <div class="bg-slate-50 rounded p-3 text-xs text-slate-600 space-y-1">
                                        <div class="flex justify-between">
                                            <span>Monthly Payments:</span>
                                            <span class="font-medium">£{{ $pricing->installment_plan['monthly_amount'] }} x {{ $pricing->installment_plan['months'] }} months</span>
                                        </div>
                                        <div class="flex justify-between border-t border-slate-200 pt-1 mt-1">
                                            <span>Total Plan Cost:</span>
                                            <span class="font-medium">£{{ $pricing->installment_plan['total_price'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @else
                         <div class="border border-slate-100 rounded-xl p-5 bg-slate-50 text-slate-400 opacity-75 cursor-not-allowed">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                <span class="font-medium">No Installment Plan Available</span>
                            </div>
                         </div>
                    @endif
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg shadow-sm transition-colors w-full sm:w-auto">
                        Confirm Selection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
