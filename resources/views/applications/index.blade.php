@extends('layouts.app')

@section('title', 'My applications | Nagarik Sewa Portal')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Citizen services</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">My applications</h1>
            <p class="mt-2 text-slate-600">Review your submissions and their tracking IDs.</p>
        </div>
        <a class="rounded-lg bg-blue-800 px-4 py-3 font-semibold text-white hover:bg-blue-900" href="{{ route('applications.create') }}">
            New application
        </a>
    </div>

    @if ($applications->isEmpty())
        <section class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h2 class="text-lg font-semibold">No applications yet</h2>
            <p class="mt-2 text-slate-600">Start a residential recommendation request when you are ready.</p>
            <a class="mt-5 inline-flex rounded-lg bg-blue-800 px-4 py-2.5 font-semibold text-white hover:bg-blue-900" href="{{ route('services.index') }}">
                Browse services
            </a>
        </section>
    @else
        <div class="mt-8 grid gap-4">
            @foreach ($applications as $application)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-semibold">{{ $application->serviceType->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $application->serviceType->department->name }}</p>
                        <p class="mt-2 font-mono text-sm text-blue-900">{{ $application->tracking_id }}</p>
                        <p class="mt-1 text-sm text-slate-500">Sent {{ $application->created_at->format('M j, Y') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-status-badge :status="$application->status" />
                        <a class="text-sm font-semibold text-blue-800 hover:underline" href="{{ route('applications.track', $application->tracking_id) }}">
                            Track
                        </a>
                        @if ($application->status === 'correction_required')
                            <a class="text-sm font-semibold text-amber-800 hover:underline"
                               href="{{ route('applications.corrections.edit', $application) }}">
                                Upload corrections
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $applications->links() }}</div>
    @endif
@endsection
