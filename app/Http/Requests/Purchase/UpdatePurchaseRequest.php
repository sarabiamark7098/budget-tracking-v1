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
            'category_id' => ['nullable', 'exists:categories,id'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'is_installment' => ['boolean'],
            'installment_count' => ['nullable', 'integer', 'min:2'],
            'installment_amount' => ['nullable', 'numeric', 'min:0'],
            'installments_paid' => ['nullable', 'integer', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
