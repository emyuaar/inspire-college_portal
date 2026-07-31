@extends('layouts.partner')
@section('title', 'Start New Conversation')
@section('active-page', 'support')
@section('content')
<div class="max-w-2xl mx-auto space-y-6 mt-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('partner.support.index') }}" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-all shadow-sm group">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 leading-tight">Start Support Case</h1>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                Staff typically responds within 24 hours
            </p>
        </div>
    </div>

    <x-ui.card class="border-slate-200 shadow-xl overflow-hidden" padding="p-0">
        <div class="bg-slate-50 px-8 py-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Request Details</span>
            <span class="text-[10px] font-bold text-slate-400 italic">Step 1 of 1</span>
        </div>
        <form action="{{ route('partner.support.store') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <div class="space-y-1.5">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Case Subject</label>
                <div class="relative">
                    <input type="text" name="subject" required 
                           class="w-full pl-4 pr-10 py-4 bg-slate-100/50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-4 focus:ring-ds-pink/5 focus:border-ds-pink transition-all" 
                           placeholder="High-level summary of your request">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                </div>
                @error('subject') <p class="mt-1 text-[10px] font-bold text-red-500 uppercase">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Category</label>
                    <select name="category" 
                            class="w-full px-4 py-4 bg-slate-100/50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:bg-white transition-all appearance-none cursor-pointer">
                        <option value="General Support">General Support</option>
                        <option value="Technical Issue">Technical Issue</option>
                        <option value="Billing & Pricing">Billing & Pricing</option>
                        <option value="Learner Onboarding">Learner Onboarding</option>
                        <option value="Course Access">Course Access</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Priority</label>
                    <select name="priority" 
                            class="w-full px-4 py-4 bg-slate-100/50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:bg-white transition-all appearance-none cursor-pointer">
                        <option value="low">Low Priority</option>
                        <option value="normal" selected>Normal Priority</option>
                        <option value="high">High Priority</option>
                        <option value="urgent">Urgent Action</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Initial Message</label>
                <textarea name="message" rows="6" required 
                          class="w-full px-4 py-4 bg-slate-100/50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-4 focus:ring-ds-pink/5 focus:border-ds-pink transition-all resize-none" 
                          placeholder="Please provide as much detail as possible to help our team assist you quickly..."></textarea>
                @error('message') <p class="mt-1 text-[10px] font-bold text-red-500 uppercase">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4">
                <button type="submit" 
                        class="w-full py-5 bg-ds-pink text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-500/10 transition-all hover:bg-blue-700 hover:shadow-2xl hover:shadow-blue-500/20 active:scale-[0.98] flex items-center justify-center gap-3 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Support Message
                </button>
            </div>
            
            <p class="text-[10px] text-center text-slate-400 font-bold uppercase tracking-widest py-2">
                Secure 256-bit Encrypted Communication
            </p>
        </form>
    </x-ui.card>
</div>
@endsection
