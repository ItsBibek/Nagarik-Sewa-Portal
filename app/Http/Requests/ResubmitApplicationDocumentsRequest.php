<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class ResubmitApplicationDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && ($this->user()?->can('resubmit', $application) ?? false);
    }

    public function rules(): array
    {
        $application = $this->route('application');
        $requiredDocuments = $application instanceof Application
            ? array_values($application->serviceType->required_documents ?? [])
            : [];

        $indexes = array_map('strval', array_keys($requiredDocuments));
        $rules = ['documents' => ['required', 'array:'.implode(',', $indexes)]];

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
        $application = $this->route('application');
        $requiredDocuments = $application instanceof Application
            ? array_values($application->serviceType->required_documents ?? [])
            : [];

        $attributes = [];

        foreach ($requiredDocuments as $index => $documentType) {
            $attributes['documents.'.$index] = str($documentType)
                ->replace('_', ' ')
                ->lower()
                ->toString();
        }

        return $attributes;
    }
}
