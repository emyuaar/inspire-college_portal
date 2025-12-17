<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Personal Information - DirectSkills Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" class="h-7" alt="DirectSkills">
                <span class="text-sm text-slate-500 hidden sm:inline">
                    Learner Portal
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600">
                    {{ $user->first_name }} {{ $user->sur_name }}
                </span>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-xs px-3 py-1.5 rounded-full bg-red-500 text-white hover:bg-red-600">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="min-h-screen bg-slate-100 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">

                <h1 class="text-xl font-semibold text-slate-800 mb-2">Personal Information</h1>
                <p class="text-sm text-slate-500 mb-4">
                    Please review your details and upload required documents.
                </p>

                <form method="POST"
                      action="{{ route('portal.profile.personal.update') }}"
                      enctype="multipart/form-data"
                      class="space-y-8">
                    @csrf

                    {{-- Basic Details (from portal.users) --}}
                    <div>
                        <h2 class="text-sm font-semibold text-slate-700 mb-3">Basic Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <label class="block text-slate-500 mb-1">First Name</label>
                                <input type="text" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ $user->first_name }}" disabled>
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Middle Name</label>
                                <input type="text" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ $user->middle_name }}" disabled>
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Surname</label>
                                <input type="text" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ $user->sur_name }}" disabled>
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Email Address</label>
                                <input type="text" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ $user->email_address }}" disabled>
                            </div>
                        </div>
                    </div>

                    {{-- Additional Details (CRM.user_detail) --}}
                    <div>
                        <h2 class="text-sm font-semibold text-slate-700 mb-3">Additional Details</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <label class="block text-slate-500 mb-1">Contact Number</label>
                                <input type="text" name="contact" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('contact', $detail->contact ?? '') }}">
                            </div>

                            <div>
                                <label class="block text-slate-500 mb-1">Date of Birth</label>
                                <input type="date" name="dob" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('dob', optional($detail)->dob ? \Carbon\Carbon::parse($detail->dob)->format('Y-m-d') : '') }}">
                            </div>

                            <div>
                                <label class="block text-slate-500 mb-1">Gender</label>
                                <select name="gender" class="w-full border-slate-200 rounded-md text-sm">
                                    <option value="">Select</option>
                                    <option value="male"   @selected(($detail->gender ?? '') === 'male')>Male</option>
                                    <option value="female" @selected(($detail->gender ?? '') === 'female')>Female</option>
                                    <option value="other"  @selected(($detail->gender ?? '') === 'other')>Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-slate-500 mb-1">Country</label>
                                <input type="text" name="country" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('country', $detail->country ?? '') }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-slate-500 mb-1">Address Line 1</label>
                                <input type="text" name="address_line_1" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('address_line_1', $detail->address_line_1 ?? '') }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-slate-500 mb-1">Address Line 2</label>
                                <input type="text" name="address_line_2" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('address_line_2', $detail->address_line_2 ?? '') }}">
                            </div>

                            <div>
                                <label class="block text-slate-500 mb-1">City</label>
                                <input type="text" name="city" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('city', $detail->city ?? '') }}">
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">State</label>
                                <input type="text" name="state" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('state', $detail->state ?? '') }}">
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Postcode / ZIP</label>
                                <input type="text" name="zip_code" class="w-full border-slate-200 rounded-md text-sm"
                                       value="{{ old('zip_code', $detail->zip_code ?? '') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="space-y-6">
                        <h2 class="text-sm font-semibold text-slate-700">Documents</h2>

                        {{-- Identity Documents --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-slate-700 text-sm">Identity Documents</span>
                                <span class="text-xs text-slate-400">PDF / JPG / PNG</span>
                            </div>
                            <input type="file" name="identity_documents[]" multiple
                                   class="block w-full text-sm text-slate-600">

                            @if($identityDocs->count())
                                <ul class="mt-2 text-xs text-slate-600 space-y-1">
                                    @foreach($identityDocs as $doc)
                                        <li>
                                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank"
                                               class="text-indigo-600 hover:underline">
                                                {{ $doc->title ?? basename($doc->file_path) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Educational Documents --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-slate-700 text-sm">Educational Documents</span>
                                <span class="text-xs text-slate-400">PDF / JPG / PNG</span>
                            </div>
                            <input type="file" name="education_documents[]" multiple
                                   class="block w-full text-sm text-slate-600">

                            @if($educationDocs->count())
                                <ul class="mt-2 text-xs text-slate-600 space-y-1">
                                    @foreach($educationDocs as $doc)
                                        <li>
                                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank"
                                               class="text-indigo-600 hover:underline">
                                                {{ $doc->title ?? basename($doc->file_path) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Experience Documents (Optional) --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-slate-700 text-sm">
                                    Experience Documents <span class="text-slate-400 text-xs">(optional)</span>
                                </span>
                                <span class="text-xs text-slate-400">PDF / JPG / PNG</span>
                            </div>
                            <input type="file" name="experience_documents[]" multiple
                                   class="block w-full text-sm text-slate-600">

                            @if($experienceDocs->count())
                                <ul class="mt-2 text-xs text-slate-600 space-y-1">
                                    @foreach($experienceDocs as $doc)
                                        <li>
                                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank"
                                               class="text-indigo-600 hover:underline">
                                                {{ $doc->title ?? basename($doc->file_path) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <a href="{{ route('portal.learner.dashboard') }}"
                           class="text-sm text-slate-500 hover:text-slate-700">
                            ← Back to dashboard
                        </a>

                        <button type="submit"
                                class="px-5 py-2 rounded-full bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                            Save Personal Information
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
