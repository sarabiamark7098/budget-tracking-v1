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
                    ->whereNull('deleted_at')
                    ->where('budget_tracking_id', $this->attributes->get('budgetTracking')?->id),
            ],
            'title'    => ['nullable', 'string', 'max:255'],
            'amount'   => [
                'nullable', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) {
                    $tracker = $this->attributes->get('budgetTracking');
                    $expense = $this->route('expense');
                    if (! $tracker) return;
                    $creditBack = $expense ? (float) $expense->amount : 0.0;
                    $available = $tracker->availableBalance(creditBack: $creditBack);
                    if ((float) $value > $available) {
                        $fail('Insufficient income balance. Available: ₱' . number_format($available, 2) . '.');
                    }
                },
            ],
            // spent_at is not editable — date is locked to when the expense was created
        ];
    }

    public function messages(): array
    {
        return [
            'budget_id.exists' => 'The selected budget does not exist or does not belong to you.',
            'amount.min'       => 'The expense amount must be greater than zero.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
