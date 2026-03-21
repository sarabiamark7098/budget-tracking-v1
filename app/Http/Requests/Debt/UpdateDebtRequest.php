<?php

namespace App\Http\Requests\Debt;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'            => ['nullable', 'in:personal,business'],
            'personal_mode'   => ['nullable', 'in:shop_pay_later,pay_installment'],
            'lender_name'     => ['nullable', 'string', 'max:255'],
            'borrower_name'   => ['nullable', 'string', 'max:255'],
            'business_name'   => ['nullable', 'string', 'max:255'],
            'amount'          => ['nullable', 'numeric', 'min:0.01'],
            'remaining_balance' => ['nullable', 'numeric', 'min:0'],
            'interest_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'months_to_pay'   => ['nullable', 'integer', 'min:1'],
            'monthly_payment' => ['nullable', 'numeric', 'min:0.01'],
            'status'          => ['nullable', 'in:active,paid'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
