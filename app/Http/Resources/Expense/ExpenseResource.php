<?php

namespace App\Http\Resources\Expense;

use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\File\FileResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'title' => $this->title,
            'amount' => $this->amount,
            'description' => $this->description,
            'spent_at' => $this->spent_at,
            'is_recurring' => $this->is_recurring,
            'recurrence_interval' => $this->recurrence_interval,
            'recurrence_end_date' => $this->recurrence_end_date,
            'files' => $this->whenLoaded('files', fn() => FileResource::collection($this->files)),
            'created_at' => $this->created_at,
        ];
    }
}
