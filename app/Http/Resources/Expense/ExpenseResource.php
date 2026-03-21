<?php

namespace App\Http\Resources\Expense;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'budget_id'  => $this->budget_id,
            'budget'     => $this->whenLoaded('budget', fn() => [
                'id'               => $this->budget->id,
                'name'             => $this->budget->name,
                'amount'           => $this->budget->amount,
                'total_budget'     => $this->budget->total_budget,
                'spent_amount'     => $this->budget->spent_amount,
                'remaining_amount' => $this->budget->remaining_amount,
            ]),
            'title'      => $this->title,
            'amount'     => $this->amount,
            'spent_at'   => $this->spent_at,
            'created_at' => $this->created_at,
        ];
    }
}
