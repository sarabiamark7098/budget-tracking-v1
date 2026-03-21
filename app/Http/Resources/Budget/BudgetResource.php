<?php

namespace App\Http\Resources\Budget;

use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'category'         => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'name'             => $this->name,
            'amount'           => $this->amount,           // per-period amount
            'total_budget'     => $this->total_budget,     // cumulative
            'period'           => $this->period,
            'start_date'       => $this->start_date,
            'spent_amount'     => $this->spent_amount,
            'remaining_amount' => $this->remaining_amount,
            'usage_percentage' => $this->usage_percentage,
            'created_at'       => $this->created_at,
        ];
    }
}
