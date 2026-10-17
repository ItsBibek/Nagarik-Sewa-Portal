<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && ($this->user()?->can('update', $application) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'remark' => [
                Rule::requiredIf(in_array($this->input('status'), [
                    ApplicationStatus::CorrectionRequired->value,
                    ApplicationStatus::Rejected->value,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
