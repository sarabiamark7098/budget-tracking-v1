<?php

namespace App\Http\Requests\Investment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'            => ['nullable', 'exists:categories,id'],
            'name'                   => ['nullable', 'string', 'max:255'],
            'type'                   => ['nullable', 'in:stocks,crypto,real_estate,business,mutual_fund,uitf,bonds,other'],
            'amount_invested'        => ['nullable', 'numeric', 'min:0'],
            'current_value'          => ['nullable', 'numeric', 'min:0'],
            'purchase_date'          => ['nullable', 'date'],
            'description'            => ['nullable', 'string'],
            // new fields
            'total_value'            => ['nullable', 'numeric', 'min:0'],
            'period'                 => ['nullable', 'in:monthly,quarterly,semi_annual,annual'],
            'months_of_payment'      => ['nullable', 'integer', 'min:1'],
            'amount_per_payment'     => ['nullable', 'numeric', 'min:0'],
            'date_started'           => ['nullable', 'date'],
            'other_investment_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
