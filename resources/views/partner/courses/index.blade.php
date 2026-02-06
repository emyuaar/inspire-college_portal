@extends('layouts.partner')

@section('title', 'My Assigned Courses')
@section('active-page', 'courses')

@section('content')
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Assigned Courses</h1>
                <p class="text-slate-500 mt-1">View your assigned courses and pricing details.</p>
            </div>

            <a href="{{ route('partner.learners.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-ds-pink hover:bg-pink-700 text-white rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="font-medium">Enroll Learner</span>
            </a>
        </div>

        @if($courses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($courses as $course)
                    <div
                        class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col h-full">

                        {{-- Card Header --}}
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                            <h3 class="font-bold text-lg text-slate-800 line-clamp-2 min-h-[3.5rem] leading-snug">
                                {{ $course->title }}
                            </h3>
                            @if($course->is_promo)
                                <span
                                    class="inline-flex items-center px-2 py-1 mt-3 rounded text-xs font-medium bg-green-100 text-green-700">
                                    Promo Active
                                </span>
                            @endif
                        </div>

                        {{-- Card Body --}}
                        <div class="p-5 space-y-4 flex-1">

                            {{-- Pricing --}}
                            <div class="space-y-3">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Pricing Options</h4>

                                {{-- Full Plan --}}
                                <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Full Payment</span>
                                    @if($course->full_plan['available'])
                                        <span class="font-bold text-slate-800">£{{ number_format($course->full_plan['amount']) }}</span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Not available</span>
                                    @endif
                                </div>

                                {{-- Installment Plan --}}
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-slate-600">Installments</span>
                                        @if($course->installment_plan['available'])
                                            <span class="font-bold text-slate-800">Available</span>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Not available</span>
                                        @endif
                                    </div>

                                    @if($course->installment_plan['available'])
                                        <div class="pt-2 mt-2 border-t border-slate-200 grid grid-cols-2 gap-2 text-xs">
                                            <div>
                                                <span class="block text-slate-400">Deposit</span>
                                                <span
                                                    class="font-semibold text-slate-700">£{{ number_format($course->installment_plan['deposit']) }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="block text-slate-400">Monthly</span>
                                                <span
                                                    class="font-semibold text-slate-700">£{{ number_format($course->installment_plan['monthly_amount']) }}</span>
                                                <span class="text-slate-400">x {{ $course->installment_plan['months'] }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Notes --}}
                            @if(!empty($course->assignment_notes))
                                <div class="pt-4 border-t border-slate-100 mt-2">
                                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2">Partner Notes</h4>
                                    <p class="text-sm text-slate-600 italic bg-amber-50 p-3 rounded border border-amber-100">
                                        "{{ $course->assignment_notes }}"
                                    </p>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-xl border border-slate-200 border-dashed">
                <div class="bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900">No Courses Assigned</h3>
                <p class="text-slate-500 max-w-sm mx-auto mt-2">
                    You currently have no courses assigned to your partner account. Please contact support if you believe this
                    is an error.
                </p>
            </div>
        @endif

    </div>
@endsection