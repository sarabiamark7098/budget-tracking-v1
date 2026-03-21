<?php

namespace App\Http\Requests\Insurance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateInsurancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_name'     => ['nullable', 'string', 'max:255'],
            'plan_name'         => ['nullable', 'string', 'max:255'],
            'coverage_type'     => ['nullable', 'array'],
            'coverage_type.*'   => ['string', 'in:life,health,vehicle,property,travel,other'],
            'coverage_amount'   => ['nullable', 'numeric', 'min:0'],
            'premium_amount'    => ['nullable', 'numeric', 'min:0'],
            'payment_frequency' => ['nullable', 'in:monthly,quarterly,semi_annually,annually'],
            'policy_number'     => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
