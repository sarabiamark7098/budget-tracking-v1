<?php

namespace App\Http\Resources\Debt;

use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'lender_name' => $this->lender_name,
            'amount' => $this->amount,
            'remaining_balance' => $this->remaining_balance,
            'interest_rate' => $this->interest_rate,
            'due_date' => $this->due_date,
            'description' => $this->description,
            'status' => $this->status,
            'type' => $this->type,
            'business_name' => $this->business_name,
            'payments' => $this->whenLoaded('payments', fn() => PaymentResource::collection($this->payments)),
            'created_at' => $this->created_at,
        ];
    }
}
