<?php

namespace App\Http\Requests\BudgetTracking;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreBudgetTrackingTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array|string> */
    public function rules(): array
    {
        return [
            'type'                           => ['required', 'in:income,expense'],
            'title'                          => ['required', 'string', 'max:255'],
            'amount'                         => ['required', 'numeric', 'min:0.01'],
            'date'                           => ['required', 'date'],
            'description'                    => ['nullable', 'string', 'max:1000'],
            'category_id'                    => ['nullable', 'exists:categories,id'],
            'budget_tracking_allocation_id'  => ['nullable', 'exists:budget_tracking_allocations,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
