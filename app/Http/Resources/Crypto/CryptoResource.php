<?php

namespace App\Http\Resources\Crypto;

use Illuminate\Http\Resources\Json\JsonResource;

class CryptoResource extends JsonResource
{
    public function toArray($request): array
    {
        $lotQty    = (float) ($this->lots_sum_quantity ?? 0);
        $soldQty   = (float) ($this->sales_sum_quantity_sold ?? 0);
        $rewardQty = (float) ($this->dividends_sum_quantity_rewarded ?? 0);

        return [
            'id'           => $this->id,
            'coin_name'    => $this->coin_name,
            'symbol'       => $this->symbol,
            'latest_price' => $this->latest_price,
            'net_quantity' => round(max(0, $lotQty + $rewardQty - $soldQty), 8),
            'created_at'   => $this->created_at,
        ];
    }
}
