<?php

namespace App\Http\Resources\Crypto;

use Illuminate\Http\Resources\Json\JsonResource;

class CryptoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'coin_name'    => $this->coin_name,
            'symbol'       => $this->symbol,
            'latest_price' => $this->latest_price,
            'created_at'   => $this->created_at,
        ];
    }
}
