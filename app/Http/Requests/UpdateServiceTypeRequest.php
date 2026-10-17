<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')
                    ->where('department_id', $this->route('serviceType')->department_id)
                    ->ignore($this->route('serviceType')->id),
            ],
            'fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
