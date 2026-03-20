<?php

namespace App\Http\Requests\Expense;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_id' => [
                'nullable',
                Rule::exists('budgets', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'title'                => ['nullable', 'string', 'max:255'],
            'amount'               => ['nullable', 'numeric', 'min:0'],
            'description'          => ['nullable', 'string'],
            'spent_at'             => ['nullable', 'date'],
            'is_recurring'         => ['boolean'],
            'recurrence_interval'  => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'recurrence_end_date'  => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'budget_id.exists' => 'The selected budget does not exist or does not belong to you.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
