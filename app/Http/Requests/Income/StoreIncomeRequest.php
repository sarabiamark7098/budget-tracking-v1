<?php

namespace App\Http\Requests\Income;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'source'      => ['nullable', 'in:Compensation Income,Business Income,Passive Income,Property Gains,Other Sources'],
            'received_at' => ['required', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
