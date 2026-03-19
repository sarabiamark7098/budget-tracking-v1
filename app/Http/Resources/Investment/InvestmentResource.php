<?php

namespace App\Http\Resources\Investment;

use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'name' => $this->name,
            'type' => $this->type,
            'amount_invested' => $this->amount_invested,
            'current_value' => $this->current_value,
            'roi' => $this->roi,
            'roi_amount' => $this->roi_amount,
            'purchase_date' => $this->purchase_date,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
