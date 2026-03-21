<?php

namespace App\Http\Requests\Income;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'amount'      => ['nullable', 'numeric', 'min:0.01'],
            'source'      => ['nullable', 'in:Compensation Income,Business Income,Passive Income,Property Gains,Other Sources'],
            'received_at' => ['nullable', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
