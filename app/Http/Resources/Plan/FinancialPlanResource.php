<?php

namespace App\Http\Resources\Plan;

use Illuminate\Http\Resources\Json\JsonResource;

class FinancialPlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'monthly_income_target' => $this->monthly_income_target,
            'monthly_expense_limit' => $this->monthly_expense_limit,
            'savings_target' => $this->savings_target,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'goals' => $this->whenLoaded('financialGoals', fn() => FinancialGoalResource::collection($this->financialGoals)),
            'created_at' => $this->created_at,
        ];
    }
}
