<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'symbol'       => $this->symbol,
            'company_name' => $this->company_name,
            'latest_price' => $this->latest_price,
            'created_at'   => $this->created_at,
        ];
    }
}
