<?php

namespace App\Http\Resources\Purchase;

use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\File\FileResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'item_name' => $this->item_name,
            'description' => $this->description,
            'total_cost' => $this->total_cost,
            'is_installment' => $this->is_installment,
            'installment_count' => $this->installment_count,
            'installment_amount' => $this->installment_amount,
            'installments_paid' => $this->installments_paid,
            'remaining_installments' => $this->remaining_installments,
            'purchase_date' => $this->purchase_date,
            'files' => $this->whenLoaded('files', fn() => FileResource::collection($this->files)),
            'created_at' => $this->created_at,
        ];
    }
}
