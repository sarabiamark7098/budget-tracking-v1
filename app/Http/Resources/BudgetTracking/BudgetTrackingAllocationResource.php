<?php

namespace App\Http\Resources\BudgetTracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetTrackingAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'allocated_amount' => (float) $this->allocated_amount,
            'spent_amount'     => $this->spent_amount,
            'remaining_amount' => $this->remaining_amount,
            'usage_percentage' => $this->usage_percentage,
            'color'            => $this->color,
            'icon'             => $this->icon,
            'category'         => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
