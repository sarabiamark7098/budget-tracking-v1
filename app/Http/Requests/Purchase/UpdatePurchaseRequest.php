<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name'         => ['nullable', 'string', 'max:255'],
            'total_cost'        => ['nullable', 'numeric', 'min:0'],
            'payment_method'    => ['nullable', 'in:cash,credit_card,other'],
            'purchase_date'     => ['nullable', 'date'],
            'installment_count' => ['nullable', 'integer', 'min:1'],
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
