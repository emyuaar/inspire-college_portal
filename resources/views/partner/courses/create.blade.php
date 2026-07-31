@extends('layouts.partner')

@section('title', 'Add Courses')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Enroll Learner in Courses</h1>
            <p class="text-slate-500">Select one or more courses for: <span class="font-semibold">{{ $learner->first_name }} {{ $learner->sur_name }}</span></p>
        </div>
        <a href="{{ route('partner.learners.show', $learner->id) }}" class="text-slate-600 hover:text-slate-900 text-sm font-medium">
             Cancel
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 rounded bg-red-50 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('partner.courses.store', $learner->id) }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Available Courses</h3>
                <span class="text-xs text-slate-500">Select courses to create enrolments</span>
            </div>

            <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                @foreach($courses as $course)
                    <div class="p-4 hover:bg-slate-50 transition-colors flex items-start gap-4">
                        <div class="pt-1">
                            <input type="checkbox" name="course_ids[]" value="{{ $course->course_id }}" id="course_{{ $course->course_id }}" class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        </div>
                        <div class="flex-1">
                            <label for="course_{{ $course->course_id }}" class="block cursor-pointer">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">{{ $course->title }}</h4>
                                        <div class="mt-1 flex gap-2">
                                            @if($course->has_partner_discount)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Partner {{ $course->discount_label }}
                                                </span>
                                            @endif
                                            
                                            @if($course->installment_plan['available'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                                    Installment Plan Available
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($course->has_partner_discount)
                                            <div class="text-sm font-bold text-slate-900">£{{ $course->final_full_price }}</div>
                                            <div class="text-xs text-slate-400 line-through">£{{ $course->base_price }}</div>
                                        @else
                                            <div class="text-sm font-bold text-slate-900">£{{ $course->final_full_price }}</div>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-6 border-t border-slate-200 bg-slate-50 flex justify-end">
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors text-sm shadow-sm flex items-center gap-2">
                    <span>Create Enrolments</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
