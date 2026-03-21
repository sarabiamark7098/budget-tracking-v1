<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name'         => ['required', 'string', 'max:255'],
            'total_cost'        => ['required', 'numeric', 'min:0'],
            'payment_method'    => ['required', 'in:cash,credit_card,other'],
            'purchase_date'     => ['required', 'date'],
            // Credit card only
            'installment_count' => ['required_if:payment_method,credit_card', 'nullable', 'integer', 'min:1'],
            'installment_amount'=> ['nullable', 'numeric', 'min:0'],
            'installments_paid' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
