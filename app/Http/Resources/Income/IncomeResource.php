<?php

namespace App\Http\Resources\Income;

use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'title'       => $this->title,
            'amount'      => $this->amount,
            'source'      => $this->source,
            'received_at' => $this->received_at,
            'created_at'  => $this->created_at,
        ];
    }
}
