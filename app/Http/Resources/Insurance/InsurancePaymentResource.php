<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'insurance_plan_id' => $this->insurance_plan_id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
