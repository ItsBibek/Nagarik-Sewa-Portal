@extends('layouts.app')

@section('title', 'Dashboard | Nagarik Sewa Portal')

@section('content')
    <section class="grid gap-6">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-800">
                {{ auth()->user()->role === 'officer' ? 'Ward staff' : 'Citizen dashboard' }}
            </p>
            <h1 class="mt-2 text-3xl font-bold">Hello, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-slate-600">
                @if (auth()->user()->role === 'citizen')
                    Apply for a residential recommendation or check a request you already sent.
                @else
                    Open your ward review queue to work on citizen requests.
                @endif
            </p>
        </header>

        @if (auth()->user()->role === 'citizen')
            <section class="grid gap-4 md:grid-cols-2">
                <a href="{{ route('services.index') }}" class="rounded-xl border border-blue-200 bg-white p-6 hover:border-blue-500">
                    <p class="text-sm font-semibold text-blue-800">ONE AVAILABLE SERVICE</p>
                    <h2 class="mt-2 text-xl font-bold">Residential recommendation letter</h2>
                    <p class="mt-2 text-sm text-slate-600">See the example fee, prepare documents, and apply.</p>
                    <span class="mt-4 inline-flex rounded-lg bg-blue-800 px-4 py-2.5 font-semibold text-white">Start an application</span>
                </a>
                <a href="{{ route('applications.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-blue-400">
                    <p class="text-sm font-semibold text-slate-600">YOUR REQUESTS</p>
                    <h2 class="mt-2 text-xl font-bold">Check progress</h2>
                    <p class="mt-2 text-sm text-slate-600">View status, tracking number, or upload requested corrections.</p>
                    <span class="mt-4 inline-flex rounded-lg border border-slate-300 px-4 py-2.5 font-semibold text-slate-800">My applications</span>
                </a>
            </section>
        @elseif (auth()->user()->role === 'officer')
            <a href="{{ route('officer.applications.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-blue-400">
                <p class="text-sm font-semibold text-blue-800">WARD OFFICE</p>
                <h2 class="mt-2 text-xl font-bold">Application review queue</h2>
                <p class="mt-2 text-sm text-slate-600">Open a request, check documents, and record the decision.</p>
                <span class="mt-4 inline-flex rounded-lg bg-blue-800 px-4 py-2.5 font-semibold text-white">Review requests</span>
            </a>
        @else
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-blue-400">
                <p class="text-sm font-semibold text-blue-800">ADMINISTRATION</p>
                <h2 class="mt-2 text-xl font-bold">Manage the service and requests</h2>
                <p class="mt-2 text-sm text-slate-600">Open service setup or the portal overview.</p>
                <span class="mt-4 inline-flex rounded-lg bg-blue-800 px-4 py-2.5 font-semibold text-white">Admin dashboard</span>
            </a>
        @endif
    </section>
@endsection
