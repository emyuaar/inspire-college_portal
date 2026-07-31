<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organization Dashboard - Inspire College Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100">
    {{-- Top nav --}}
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/1000ppi/logo.png') }}"
                     class="h-7" alt="Inspire College">
                <span class="text-sm text-slate-500 hidden sm:inline">
                    Organization Portal
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

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        {{-- Top cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <section class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-2">Account Summary</h2>
                <div class="text-xs text-slate-600 space-y-1">
                    <p><span class="font-medium">Organization:</span> {{ $user->first_name }} {{ $user->sur_name }}</p>
                    <p><span class="font-medium">Email:</span> {{ $user->email_address }}</p>
                    <p><span class="font-medium">Account Type:</span> Organization</p>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm md:col-span-2">
                <h2 class="text-sm font-semibold text-slate-800 mb-2">Overview</h2>
                <p class="text-xs text-slate-500">
                    This section can later show number of linked learners, active courses, and other KPIs
                    based on system integration.
                </p>
            </section>
        </div>

        {{-- Learners table --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Linked Learners</h2>
                <span class="text-[11px] text-slate-400">
                    Total: {{ $learners->count() }}
                </span>
            </div>

            @if($learners->isEmpty())
                <p class="text-xs text-slate-500">
                    No learners are linked to this organization yet.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left text-slate-600 border-t border-slate-100">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($learners as $learner)
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        {{ $learner->first_name }} {{ $learner->sur_name }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ $learner->email_address }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ optional($learner->created_at)->format('d M Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
