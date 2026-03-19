<?php

namespace App\Http\Resources\Income;

use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'title' => $this->title,
            'amount' => $this->amount,
            'source' => $this->source,
            'description' => $this->description,
            'received_at' => $this->received_at,
            'is_recurring' => $this->is_recurring,
            'recurrence_interval' => $this->recurrence_interval,
            'recurrence_end_date' => $this->recurrence_end_date,
            'created_at' => $this->created_at,
        ];
    }
}
