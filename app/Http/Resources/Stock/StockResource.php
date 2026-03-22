<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        $lotShares  = (float) ($this->lots_sum_shares ?? 0);
        $soldShares = (float) ($this->sales_sum_shares_sold ?? 0);

        return [
            'id'           => $this->id,
            'symbol'       => $this->symbol,
            'company_name' => $this->company_name,
            'latest_price' => $this->latest_price,
            'net_shares'   => round(max(0, $lotShares - $soldShares), 4),
            'created_at'   => $this->created_at,
        ];
    }
}
