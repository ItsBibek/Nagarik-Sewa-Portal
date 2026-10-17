@extends('layouts.app')

@section('title', 'Submit corrections | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-blue-800 hover:underline" href="{{ route('applications.index') }}">
            ← Back to my applications
        </a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-teal-700">Correction requested</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Upload replacement documents</h1>
        <p class="mt-3 text-slate-600">
            Application {{ $application->tracking_id }} · {{ $application->serviceType->name }}
        </p>
        @if ($correctionRemark)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <p class="font-semibold">Correction requested by the ward office</p>
                <p class="mt-2 whitespace-pre-line">{{ $correctionRemark }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">Please check the following:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="mt-6 grid gap-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
              method="POST" action="{{ route('applications.corrections.store', $application) }}"
              enctype="multipart/form-data">
            @csrf
            <p class="text-sm text-slate-600">
                Upload a complete replacement set. Earlier documents remain in the application record for audit purposes.
                PDF, JPG, JPEG, or PNG files up to 5 MB are accepted.
            </p>
            @foreach ($requiredDocuments as $index => $documentType)
                <div class="grid gap-2">
                    <label class="text-sm font-semibold" for="document-{{ $index }}">
                        {{ str($documentType)->replace('_', ' ')->title() }}
                    </label>
                    <input class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-800"
                           id="document-{{ $index }}" name="documents[{{ $index }}]" type="file"
                           accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            @endforeach
            <button class="w-fit rounded-lg bg-blue-800 px-5 py-3 font-semibold text-white hover:bg-blue-900" type="submit">
                Submit replacement documents
            </button>
        </form>
    </section>
@endsection
