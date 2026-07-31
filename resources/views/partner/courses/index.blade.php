@extends('layouts.partner')

@section('title', 'My Assigned Courses')
@section('active-page', 'courses')

@section('content')
    <div class="space-y-8 pb-12">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Course Catalogue</h1>
                <p class="text-slate-500 mt-2 font-medium flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-ds-pink"></i>
                    Your assigned courses and exclusive partner pricing.
                </p>
            </div>

            <div class="flex items-center gap-3">
                 {{-- Search Header --}}
                 <form action="{{ route('partner.courses.index') }}" method="GET" class="relative group">
                    <input type="text" name="search" value="{{ $search }}" 
                        placeholder="Search courses..."
                        class="pl-10 pr-4 py-2.5 w-64 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-ds-pink/20 focus:border-ds-pink transition-all shadow-sm">
                    <i class="fa-solid fa-search absolute left-4 top-3 text-slate-400 group-focus-within:text-ds-pink transition-colors"></i>
                 </form>

                 <a href="{{ route('partner.learners.create') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-ds-navy hover:bg-slate-800 text-white rounded-xl transition-all shadow-md hover:shadow-lg font-bold text-sm">
                    <i class="fa-solid fa-user-plus"></i>
                    Enroll New Learner
                </a>
            </div>
        </div>

        @if($courses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($courses as $course)
                    <div class="group bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                        
                        {{-- Course Image & Badges --}}
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                            @if($course->image)
                                @php 
                                    $imgSrc = str_starts_with($course->image, 'http') ? $course->image : 'https://inspirecollegeoflearning.com/storage/' . $course->image;
                                @endphp
                                <img src="{{ $imgSrc }}" 
                                     alt="{{ $course->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
                                    <i class="fa-solid fa-building-columns text-4xl text-slate-200"></i>
                                </div>
                            @endif

                            {{-- Category Overlay --}}
                            @if($course->category)
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 bg-white/90 backdrop-blur text-[10px] font-black text-ds-navy uppercase tracking-widest rounded-full shadow-sm">
                                    {{ $course->category->name }}
                                </span>
                            </div>
                            @endif

                            {{-- Pricing Badges Overlay --}}
                            <div class="absolute bottom-4 left-4 flex gap-2">
                                @if($course->allow_two)
                                    <span class="px-2.5 py-1 bg-cyan-500 text-white text-[9px] font-black uppercase rounded shadow-sm">2 Months</span>
                                @endif
                                @if($course->allow_three)
                                    <span class="px-2.5 py-1 bg-indigo-500 text-white text-[9px] font-black uppercase rounded shadow-sm">3 Months</span>
                                @endif
                                @if($course->installment_plan['available'])
                                    <span class="px-2.5 py-1 bg-emerald-500 text-white text-[9px] font-black uppercase rounded shadow-sm">
                                        Installments Available
                                    </span>
                                @endif
                                @if($course->full_plan['available'])
                                    <span class="px-2.5 py-1 bg-blue-500 text-white text-[9px] font-black uppercase rounded shadow-sm">
                                        Full Pay Ready
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Header --}}
                        <div class="p-6 pb-0">
                            <h3 class="font-black text-lg text-slate-800 leading-tight line-clamp-2 min-h-[3rem] group-hover:text-ds-pink transition-colors">
                                {{ $course->title }}
                            </h3>
                            @if(!empty($course->excerpt))
                                <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ $course->excerpt }}</p>
                            @endif
                        </div>

                        {{-- Card Body: Pricing --}}
                        <div class="p-6 space-y-5 flex-1">
                            
                            {{-- Pricing Grid --}}
                            <div class="space-y-3">
                                {{-- Full Payment Row --}}
                                <div class="flex justify-between items-center p-3 rounded-2xl bg-slate-50 border border-slate-100 group/row hover:bg-blue-50/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-blue-500 shadow-sm">
                                            <i class="fa-solid fa-credit-card text-xs"></i>
                                        </div>
                                        <span class="text-xs font-bold text-slate-600">Full Price</span>
                                    </div>
                                    @if($course->full_plan['available'])
                                        <span class="font-black text-slate-900">£{{ number_format($course->full_plan['amount'], 2) }}</span>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-bold uppercase italic">Restricted</span>
                                    @endif
                                </div>

                                {{-- Installment Row --}}
                                @if($course->installment_plan['available'])
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-4 group/inst hover:bg-emerald-50/30 transition-colors">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-emerald-500 shadow-sm">
                                                <i class="fa-solid fa-calendar-check text-xs"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600">Installments</span>
                                        </div>
                                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded">Enabled</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200/60">
                                        <div>
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Deposit</span>
                                            <span class="text-sm font-black text-slate-800">£{{ number_format($course->installment_plan['deposit'], 2) }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Plan</span>
                                            <span class="text-sm font-black text-slate-800">£{{ number_format($course->installment_plan['monthly_amount'], 2) }} <span class="text-[10px] font-bold text-slate-400">/mo</span></span>
                                            <span class="block text-[10px] font-bold text-emerald-600 uppercase">{{ $course->installment_plan['months'] + 1 }} Months</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Partner Commercial Notes --}}
                            @if(!empty($course->assignment_notes))
                                <div class="p-4 rounded-2xl bg-ds-pink/5 border border-ds-pink/10 flex gap-3">
                                    <i class="fa-solid fa-circle-info text-ds-pink mt-0.5 text-xs"></i>
                                    <div>
                                        <p class="text-[10px] font-black text-ds-pink uppercase tracking-widest mb-1">Commercial Note</p>
                                        <p class="text-xs text-slate-600 font-medium leading-relaxed italic">
                                            "{{ $course->assignment_notes }}"
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Footer Actions --}}
                        <div class="p-6 pt-0 mt-auto flex items-center gap-2">
                             <a href="{{ route('partner.learners.create') }}?course_id={{ $course->id }}" 
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-ds-pink text-white rounded-xl text-xs font-black shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-xl transition-all">
                                <i class="fa-solid fa-plus-circle"></i>
                                Enroll Now
                            </a>
                            <a href="https://inspirecollegeoflearning.com/courses/{{ $course->category->slug ?? 'health-and-social-care-management' }}/{{ $course->slug }}" target="_blank"
                               class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-black hover:bg-slate-200 transition-colors">
                               Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-20 bg-white rounded-[2rem] border border-slate-100 shadow-sm flex flex-col items-center">
                <div class="w-24 h-24 rounded-full bg-slate-50 flex items-center justify-center mb-6">
                    <i class="fa-solid fa-swatchbook text-4xl text-slate-300"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Catalogue Empty</h3>
                <p class="text-slate-500 max-w-sm mx-auto mt-3 font-medium">
                    {{ $search ? 'No courses match your search criteria. Try a different term or clear the filter.' : 'You currently have no courses assigned to your account.' }}
                </p>
                @if($search)
                <a href="{{ route('partner.courses.index') }}" class="mt-8 px-6 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg">
                    Clear Search
                </a>
                @endif
            </div>
        @endif
    </div>

    </div>
@endsection
