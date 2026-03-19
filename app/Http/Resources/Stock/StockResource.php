<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'symbol' => $this->symbol,
            'company_name' => $this->company_name,
            'shares' => $this->shares,
            'buy_price' => $this->buy_price,
            'current_price' => $this->current_price,
            'current_value' => $this->current_value,
            'profit_loss' => $this->profit_loss,
            'profit_loss_percentage' => $this->profit_loss_percentage,
            'purchase_date' => $this->purchase_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
