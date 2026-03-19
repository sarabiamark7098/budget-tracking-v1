<?php

namespace App\Http\Resources\Plan;

use Illuminate\Http\Resources\Json\JsonResource;

class FinancialGoalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'financial_plan_id' => $this->financial_plan_id,
            'name' => $this->name,
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'deadline' => $this->deadline,
            'priority' => $this->priority,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'created_at' => $this->created_at,
        ];
    }
}
