@extends('layouts.app')

@section('title', 'Review application | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto grid max-w-4xl gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a class="text-sm font-semibold text-blue-800 hover:underline" href="{{ route('officer.applications.index') }}">
                    ← Back to review queue
                </a>
                <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-teal-700">Application review</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $application->serviceType->name }}</h1>
                <p class="mt-2 font-mono text-sm text-blue-900">{{ $application->tracking_id }}</p>
            </div>
            <x-status-badge :status="$application->status" />
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-7">
            <div>
                <h2 class="font-semibold">Applicant</h2>
                <p class="mt-2 text-slate-700">{{ $application->user->name }}</p>
                <p class="text-sm text-slate-600">{{ $application->user->email }}</p>
                @if ($application->user->phone)
                    <p class="text-sm text-slate-600">{{ $application->user->phone }}</p>
                @endif
            </div>
            <div>
                <h2 class="font-semibold">Service and department</h2>
                <p class="mt-2 text-slate-700">{{ $application->serviceType->name }}</p>
                <p class="text-sm text-slate-600">{{ $application->serviceType->department->name }}</p>
                <p class="mt-2 text-sm text-slate-500">Received {{ $application->created_at->format('M j, Y g:i A') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold">Submitted documents</h2>
            @if ($application->documents->isEmpty())
                <p class="mt-3 text-sm text-slate-600">No documents are attached.</p>
            @else
                <ul class="mt-4 grid gap-3">
                    @foreach ($application->documents as $document)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3">
                            <div>
                                <p class="font-medium">{{ str($document->document_type)->replace('_', ' ')->title() }}</p>
                                <p class="text-xs text-slate-500">Uploaded {{ $document->uploaded_at?->format('M j, Y g:i A') }}</p>
                            </div>
                            <a class="font-semibold text-blue-800 hover:underline"
                               href="{{ route('applications.documents.download', [$application, $document]) }}">
                                Download securely
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @can('update', $application)
            @if ($nextStatuses !== [])
                <details class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm sm:p-7" open>
                    <summary class="cursor-pointer text-lg font-bold">Update application status</summary>
                    <form class="mt-5 grid gap-4" method="POST"
                          action="{{ route('officer.applications.status.update', $application) }}">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-2">
                            <label for="status" class="text-sm font-semibold">New status</label>
                            <select id="status" name="status" required
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-3">
                                @foreach ($nextStatuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status') === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="remark-field" class="grid gap-2">
                            <label for="remark" class="text-sm font-semibold">
                                Staff remark <span id="remark-required" class="text-red-700"></span>
                            </label>
                            <textarea id="remark" name="remark" rows="4" maxlength="2000"
                                      class="rounded-lg border border-slate-300 px-3 py-2.5"
                                      placeholder="Explain the requested correction or rejection reason">{{ old('remark') }}</textarea>
                            <p class="text-xs text-slate-500">A remark is required when requesting a correction or rejecting an application.</p>
                        </div>
                        <button class="w-fit rounded-lg bg-blue-800 px-5 py-3 font-semibold text-white hover:bg-blue-900" type="submit">
                            Save status
                        </button>
                    </form>
                </details>
            @endif
        @endcan

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold">Status history</h2>
            <ol class="mt-5 grid gap-4">
                @foreach ($application->statusHistories as $history)
                    <li class="border-l-2 border-blue-200 pl-4">
                        <p class="font-semibold">{{ str($history->new_status)->replace('_', ' ')->title() }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $history->created_at->format('M j, Y g:i A') }}
                            · {{ $history->changedBy->name }}
                        </p>
                        @if ($history->remark)
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $history->remark }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </section>

    <script>
        const statusSelect = document.getElementById('status');
        const remarkInput = document.getElementById('remark');
        const requiredMark = document.getElementById('remark-required');

        function updateRemarkRequirement() {
            const required = ['correction_required', 'rejected'].includes(statusSelect?.value);
            remarkInput.required = required;
            requiredMark.textContent = required ? '(required)' : '(optional)';
        }

        statusSelect?.addEventListener('change', updateRemarkRequirement);
        if (statusSelect) updateRemarkRequirement();
    </script>
@endsection
