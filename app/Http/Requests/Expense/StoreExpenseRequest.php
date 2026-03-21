<?php

namespace App\Http\Requests\Expense;

use App\Models\Budget;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_id' => [
                'required',
                Rule::exists('budgets', 'id')
                    ->whereNull('deleted_at')
                    ->where('budget_tracking_id', $this->attributes->get('budgetTracking')?->id),
            ],
            'title'    => ['required', 'string', 'max:255'],
            'amount'   => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) {
                    $tracker = $this->attributes->get('budgetTracking');
                    if (! $tracker) return;
                    $available = $tracker->availableBalance();
                    if ((float) $value > $available) {
                        $fail('Insufficient income balance. Available: ₱' . number_format($available, 2) . '.');
                    }
                },
            ],
            'spent_at' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $budget = Budget::find($this->input('budget_id'));
                    if ($budget && $budget->start_date && $value < $budget->start_date->toDateString()) {
                        $fail("The expense date cannot be before the budget's start date ({$budget->start_date->toDateString()}).");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'budget_id.required' => 'Please select a budget for this expense.',
            'budget_id.exists'   => 'The selected budget does not exist or does not belong to you.',
            'amount.min'         => 'The expense amount must be greater than zero.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422)
        );
    }
}
