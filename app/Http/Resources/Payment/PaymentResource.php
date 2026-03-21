<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\Debt\DebtResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'debt_id'            => $this->debt_id,
            'debt'               => $this->whenLoaded('debt', fn() => new DebtResource($this->debt)),
            'amount'             => $this->amount,
            'interest_paid'      => $this->interest_paid,
            'principal_paid'     => $this->principal_paid,
            'days_elapsed'       => $this->days_elapsed,
            'installment_number' => $this->installment_number,
            'payment_date'       => $this->payment_date?->toDateString(),
            'note'               => $this->note,
            'created_at'         => $this->created_at,
        ];
    }
}
