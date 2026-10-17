@extends('layouts.app')

@section('title', 'New application | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-blue-800 hover:underline" href="{{ route('services.index') }}">← Back to services</a>
        <div class="mt-4">
            <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Citizen services</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Start an application</h1>
            <p class="mt-3 text-slate-600">Choose the service, check the example fee, and attach the requested documents. PDF, JPG, JPEG, or PNG files up to 5 MB are accepted.</p>
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                Example fee and document requirements only; confirm the official process with your ward office.
            </p>
        </div>

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
              method="POST" action="{{ route('applications.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid gap-2">
                <label class="text-sm font-semibold" for="service_type_id">Service</label>
                <select class="rounded-lg border border-slate-300 bg-white px-3 py-3 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20"
                        id="service_type_id" name="service_type_id" required>
                    <option value="">Select a service</option>
                    @foreach ($departments as $department)
                        <optgroup label="{{ $department->name }}">
                            @foreach ($department->serviceTypes as $serviceType)
                                <option value="{{ $serviceType->id }}"
                                    @selected((string) old('service_type_id', $selectedServiceTypeId) === (string) $serviceType->id)>
                                    {{ $serviceType->name }} · NPR {{ number_format((float) $serviceType->fee, 2) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div id="service-summary" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" aria-live="polite"></div>

            <section aria-live="polite">
                <h2 class="text-lg font-semibold">Required documents</h2>
                <p class="mt-1 text-sm text-slate-500">Document requirements update when you choose a service.</p>
                <div id="required-documents" class="mt-4 grid gap-4">
                    <p id="document-placeholder" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                        Select a service to see its document requirements.
                    </p>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button class="rounded-lg bg-blue-800 px-5 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2" type="submit">
                    Submit application
                </button>
                <a class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50" href="{{ route('services.index') }}">
                    Cancel
                </a>
            </div>
        </form>
    </section>

    <script>
        const documentRequirements = @js($documentRequirements);
        const serviceDetails = @js($serviceDetails);
        const serviceSelect = document.getElementById('service_type_id');
        const documentContainer = document.getElementById('required-documents');
        const serviceSummary = document.getElementById('service-summary');

        function renderRequiredDocuments() {
            documentContainer.replaceChildren();

            const requiredDocuments = documentRequirements[serviceSelect.value] ?? [];
            const service = serviceDetails[serviceSelect.value];

            serviceSummary.textContent = service
                ? `Example fee: NPR ${Number(service.fee).toLocaleString('en-NP')} · Sample processing time: ${service.processing_days} days`
                : 'Select the residential recommendation service to see example fee and processing time.';

            if (requiredDocuments.length === 0) {
                const placeholder = document.createElement('p');
                placeholder.className = 'rounded-lg bg-slate-50 p-4 text-sm text-slate-600';
                placeholder.textContent = serviceSelect.value
                    ? 'No documents are required for this service.'
                    : 'Select a service to see its document requirements.';
                documentContainer.append(placeholder);
                return;
            }

            requiredDocuments.forEach((documentType, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'grid gap-2';

                const label = document.createElement('label');
                label.className = 'text-sm font-medium';
                label.htmlFor = `document-${index}`;
                label.textContent = documentType.replaceAll('_', ' ').replace(/\b\w/g, character => character.toUpperCase());

                const input = document.createElement('input');
                input.className = 'rounded-lg border border-slate-300 bg-white px-3 py-2.5 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-800 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20';
                input.id = `document-${index}`;
                input.name = `documents[${index}]`;
                input.type = 'file';
                input.accept = '.pdf,.jpg,.jpeg,.png';
                input.required = true;

                const hint = document.createElement('p');
                hint.className = 'text-xs text-slate-500';
                hint.textContent = 'PDF, JPG, JPEG, or PNG · 5 MB maximum';

                wrapper.append(label, input, hint);
                documentContainer.append(wrapper);
            });
        }

        serviceSelect.addEventListener('change', renderRequiredDocuments);
        renderRequiredDocuments();
    </script>
@endsection
