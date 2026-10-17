@php
    [$label, $classes] = match ($status) {
        'submitted' => ['Submitted', 'bg-red-100 text-red-800'],
        'under_review' => ['Under review', 'bg-amber-100 text-amber-900'],
        'correction_required' => ['Correction needed', 'bg-orange-100 text-orange-900'],
        'approved' => ['Approved', 'bg-green-100 text-green-800'],
        'rejected' => ['Rejected', 'bg-red-100 text-red-800'],
        default => [str($status)->replace('_', ' ')->title(), 'bg-slate-100 text-slate-700'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-3 py-1.5 text-sm font-semibold '.$classes]) }}>
    {{ $label }}
</span>
