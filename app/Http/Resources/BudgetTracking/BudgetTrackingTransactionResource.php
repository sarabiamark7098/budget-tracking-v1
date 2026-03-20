<?php

namespace App\Http\Resources\BudgetTracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetTrackingTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'title'       => $this->title,
            'amount'      => (float) $this->amount,
            'description' => $this->description,
            'date'        => $this->date,
            'added_by'    => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'category'   => $this->whenLoaded('category', fn() => [
                'id'   => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'allocation' => $this->whenLoaded('allocation', fn() => $this->allocation ? [
                'id'   => $this->allocation->id,
                'name' => $this->allocation->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
