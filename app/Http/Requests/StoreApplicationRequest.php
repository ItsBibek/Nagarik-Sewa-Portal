<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Application::class) ?? false;
    }

    public function rules(): array
    {
        $serviceTypeId = $this->input('service_type_id');
        $serviceType = is_numeric($serviceTypeId)
            ? ServiceType::query()->find($serviceTypeId)
            : null;

        $rules = [
            'service_type_id' => [
                'required',
                'integer',
                Rule::exists('service_types', 'id')->where('is_active', true),
            ],
            'documents' => ['required', 'array'],
        ];

        if ($serviceType === null) {
            return $rules;
        }

        $requiredDocuments = array_values($serviceType->required_documents ?? []);
        $documentIndexes = array_map('strval', array_keys($requiredDocuments));
        $rules['documents'] = ['required', 'array:'.implode(',', $documentIndexes)];

        foreach ($requiredDocuments as $index => $documentType) {
            $rules['documents.'.$index] = [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'extensions:pdf,jpg,jpeg,png',
                'max:5120',
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $serviceTypeId = $this->input('service_type_id');
        $serviceType = is_numeric($serviceTypeId)
            ? ServiceType::query()->find($serviceTypeId)
            : null;

        $attributes = [];

        foreach (array_values($serviceType?->required_documents ?? []) as $index => $documentType) {
            $attributes['documents.'.$index] = str($documentType)->replace('_', ' ')->lower()->toString();
        }

        return $attributes;
    }
}
