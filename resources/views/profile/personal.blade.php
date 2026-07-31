<x-app-layout page-title="Personal Information" active-page="profile">

    <x-ui.card title="Personal Information" subtitle="Review details & upload required documents">
        
        <form method="POST" action="{{ route('portal.profile.personal.update') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-bold">Please correct the following before saving:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 1. Basic Details (Read Only) --}}
            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Basic Details</h3>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-600">READ ONLY</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-ui.input label="First Name" value="{{ $user->first_name }}" disabled />
                    <x-ui.input label="Middle Name" value="{{ $user->middle_name }}" disabled />
                    <x-ui.input label="Surname" value="{{ $user->sur_name }}" disabled />
                    <x-ui.input label="Email Address" value="{{ $user->email_address }}" disabled />
                </div>
            </div>

            {{-- 2. Additional Details --}}
            <div>
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-ds-navy">Additional Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-ui.input 
                        label="Contact Number" 
                        name="contact" 
                        value="{{ old('contact', $detail->contact ?? '') }}" 
                        placeholder="e.g. +44 7700 900000"
                    />

                    <x-ui.input 
                        label="Personal Email Address" 
                        name="personal_email" 
                        type="email"
                        value="{{ old('personal_email', $detail->personal_email ?? '') }}" 
                        placeholder="e.g. learner@example.com"
                    />

                    <x-ui.input 
                        type="date" 
                        label="Date of Birth" 
                        name="dob" 
                        value="{{ old('dob', optional($detail)->dob ? \Carbon\Carbon::parse($detail->dob)->format('Y-m-d') : '') }}" 
                    />

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Gender</label>
                        <select name="gender" class="block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 focus:bg-white focus:border-ds-pink focus:ring-ds-pink transition-all text-sm shadow-sm">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(($detail->gender ?? '') === 'male')>Male</option>
                            <option value="female" @selected(($detail->gender ?? '') === 'female')>Female</option>
                            <option value="other" @selected(($detail->gender ?? '') === 'other')>Other</option>
                        </select>
                    </div>

                    <x-ui.input 
                        label="Country" 
                        name="country" 
                        value="{{ old('country', $detail->country ?? '') }}" 
                    />

                    <div class="md:col-span-2">
                         <x-ui.input 
                            label="Address Line 1" 
                            name="address_line_1" 
                            value="{{ old('address_line_1', $detail->address_line_1 ?? '') }}" 
                        />
                    </div>
                    
                    <div class="md:col-span-2">
                         <x-ui.input 
                            label="Address Line 2" 
                            name="address_line_2" 
                            value="{{ old('address_line_2', $detail->address_line_2 ?? '') }}" 
                        />
                    </div>

                    <x-ui.input 
                        label="City" 
                        name="city" 
                        value="{{ old('city', $detail->city ?? '') }}" 
                    />

                    <x-ui.input 
                        label="State / County" 
                        name="state" 
                        value="{{ old('state', $detail->state ?? '') }}" 
                    />

                    <x-ui.input 
                        label="Postcode / ZIP" 
                        name="zip_code" 
                        value="{{ old('zip_code', $detail->zip_code ?? '') }}" 
                    />
                </div>
            </div>

            {{-- 3. Documents --}}
            <div>
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-ds-navy">Documents</h3>
                    <span class="text-xs text-slate-400">PDF, JPG, PNG allowed · Max 20MB per file</span>
                </div>

                <div class="space-y-4">
                    
                    {{-- Identity --}}
                    <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Identity Documents <span class="text-slate-500 font-normal text-xs ml-1">(Multiple allowed)</span></label>
                        <input type="file" name="identity_documents[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-ds-navy file:text-white hover:file:bg-[#0B1220] transition-all"/>
                        
                        @if($identityDocs->count())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($identityDocs as $doc)
                                    <a href="{{ route('portal.profile.document.download', $doc->id) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-ds-navy hover:bg-slate-50 hover:border-ds-navy transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        {{ $doc->title ?? basename($doc->file_path) }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Education --}}
                    <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Educational Documents <span class="text-slate-500 font-normal text-xs ml-1">(Multiple allowed)</span></label>
                        <input type="file" name="education_documents[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-ds-navy file:text-white hover:file:bg-[#0B1220] transition-all"/>
                        
                         @if($educationDocs->count())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($educationDocs as $doc)
                                    <a href="{{ route('portal.profile.document.download', $doc->id) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-ds-navy hover:bg-slate-50 hover:border-ds-pink transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        {{ $doc->title ?? basename($doc->file_path) }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Experience --}}
                     <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Experience Documents <span class="text-slate-500 font-normal text-xs ml-1">(Optional)</span></label>
                        <input type="file" name="experience_documents[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-ds-navy file:text-white hover:file:bg-[#0B1220] transition-all"/>
                        
                         @if($experienceDocs->count())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($experienceDocs as $doc)
                                    <a href="{{ route('portal.profile.document.download', $doc->id) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-ds-navy hover:bg-slate-50 hover:border-slate-500 transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        {{ $doc->title ?? basename($doc->file_path) }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                <x-ui.button variant="ghost" href="{{ route('portal.learner.dashboard') }}">
                    ← Back to Dashboard
                </x-ui.button>
                
                <x-ui.button type="submit" variant="primary">
                    Save Changes
                </x-ui.button>
            </div>

        </form>
    </x-ui.card>

</x-app-layout>
