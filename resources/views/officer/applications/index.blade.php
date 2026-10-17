@extends('layouts.app')

@section('title', 'Officer queue | Nagarik Sewa Portal')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Selected ward office</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Application review queue</h1>
            <p class="mt-2 text-slate-600">Applications for services handled by your department.</p>
        </div>
        <form method="GET" action="{{ route('officer.applications.index') }}" class="flex items-end gap-2">
            <div class="grid gap-1">
                <label for="status" class="text-sm font-medium">Filter by status</label>
                <select id="status" name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-blue-800 px-4 py-2 font-semibold text-white hover:bg-blue-900" type="submit">
                Filter
            </button>
        </form>
    </div>

    @if ($applications->isEmpty())
        <section class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h2 class="text-lg font-semibold">No applications in this queue</h2>
            <p class="mt-2 text-slate-600">Applications assigned to your department will appear here.</p>
        </section>
    @else
        <div class="mt-8 grid gap-4">
            @foreach ($applications as $application)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-semibold">{{ $application->serviceType->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Applicant: {{ $application->user->name }}</p>
                        <p class="mt-1 font-mono text-sm text-blue-900">{{ $application->tracking_id }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Submitted {{ $application->created_at->format('M j, Y') }}
                            @if ($application->currentOfficer)
                                · Assigned to {{ $application->currentOfficer->name }}
                            @else
                                · Awaiting officer assignment
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-status-badge :status="$application->status" />
                        <a class="font-semibold text-blue-800 hover:underline"
                           href="{{ route('officer.applications.show', $application) }}">
                            Review
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $applications->links() }}</div>
    @endif
@endsection
