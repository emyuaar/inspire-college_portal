<x-app-layout page-title="RPL Information" active-page="profile">
    
    <div class="max-w-2xl mx-auto">
        <x-ui.card title="RPL Information" subtitle="Recognition of Prior Learning">
            
            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                If you do not have any prior learning / qualifications to declare at this stage, please confirm below.
            </p>

            <form method="POST" action="{{ route('portal.profile.rpl.update') }}">
                @csrf

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="confirm_no_rpl" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-ds-pink focus:ring-ds-pink">
                        <span class="text-sm font-medium text-slate-800">
                             I confirm that I <strong class="text-ds-navy">do not have any RPL (Recognition of Prior Learning) information</strong> to submit at this time.
                        </span>
                    </label>
                    @error('confirm_no_rpl')
                        <p class="text-xs text-red-600 mt-2 ml-7 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2">
                    <x-ui.button variant="ghost" href="{{ route('portal.learner.dashboard') }}">
                        ← Back to Dashboard
                    </x-ui.button>
                    
                    <x-ui.button type="submit" variant="primary">
                        Save Confirmation
                    </x-ui.button>
                </div>
            </form>

        </x-ui.card>
    </div>

</x-app-layout>
