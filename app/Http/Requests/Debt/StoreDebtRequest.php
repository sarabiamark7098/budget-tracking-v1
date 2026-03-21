<?php

namespace App\Http\Requests\Debt;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'            => ['required', 'in:personal,business'],
            'personal_mode'   => ['required_if:type,personal', 'nullable', 'in:shop_pay_later,pay_installment'],
            'lender_name'     => ['required', 'string', 'max:255'],
            'borrower_name'   => ['required_if:type,business', 'nullable', 'string', 'max:255'],
            'business_name'   => ['nullable', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'interest_rate'   => ['required_if:type,business', 'nullable', 'numeric', 'min:0', 'max:100'],
            'months_to_pay'   => ['required_if:personal_mode,pay_installment', 'nullable', 'integer', 'min:1'],
            'monthly_payment' => ['required_if:personal_mode,pay_installment', 'nullable', 'numeric', 'min:0.01'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
