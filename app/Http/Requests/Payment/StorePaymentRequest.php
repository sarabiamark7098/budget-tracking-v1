<?php

namespace App\Http\Requests\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_id'      => ['required', 'exists:debts,id'],
            'payment_date' => ['required', 'date'],
            'note'         => ['nullable', 'string'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'The payment amount must be greater than zero.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
