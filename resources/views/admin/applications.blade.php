@extends('layouts.app')

@section('title', 'All requests | Nagarik Sewa Portal')

@section('content')
    <section>
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-800 hover:underline">← Admin dashboard</a>
        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-800">Administration</p>
                <h1 class="mt-2 text-3xl font-bold">All citizen requests</h1>
                <p class="mt-2 text-slate-600">Read-only overview of service requests and their progress.</p>
            </div>
            <form method="GET" action="{{ route('admin.applications.index') }}" class="flex items-end gap-2">
                <div class="grid gap-1">
                    <label for="status" class="text-sm font-medium">Status</label>
                    <select id="status" name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                        <option value="">All requests</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-lg bg-blue-800 px-4 py-2 font-semibold text-white" type="submit">Filter</button>
            </form>
        </div>

        @if ($applications->isEmpty())
            <div class="mt-7 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                No requests match this filter.
            </div>
        @else
            <div class="mt-7 grid gap-3">
                @foreach ($applications as $application)
                    <article class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5">
                        <div>
                            <p class="font-semibold">{{ $application->serviceType->name }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $application->user->name }} · {{ $application->serviceType->department->name }}</p>
                            <p class="mt-1 font-mono text-sm text-slate-600">{{ $application->tracking_id }}</p>
                        </div>
                        <x-status-badge :status="$application->status" />
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $applications->links() }}</div>
        @endif
    </section>
@endsection
