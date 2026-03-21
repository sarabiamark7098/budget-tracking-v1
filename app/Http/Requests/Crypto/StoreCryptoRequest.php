<?php

namespace App\Http\Requests\Crypto;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCryptoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $btId = $this->attributes->get('budgetTracking')?->id;

        return [
            'coin_name' => ['required', 'string', 'max:255'],
            'symbol'    => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('crypto_assets')->where('budget_tracking_id', $btId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.unique' => 'This coin symbol already exists in your portfolio.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors  = $validator->errors();
        $message = $errors->first() ?: 'Validation failed';

        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => $message, 'errors' => $errors], 422)
        );
    }
}
