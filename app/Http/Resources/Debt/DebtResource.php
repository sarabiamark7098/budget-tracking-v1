<?php

namespace App\Http\Resources\Debt;

use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'type'              => $this->type,
            'personal_mode'     => $this->personal_mode,
            'lender_name'       => $this->lender_name,
            'borrower_name'     => $this->borrower_name,
            'business_name'     => $this->business_name,
            'amount'            => $this->amount,
            'remaining_balance' => $this->remaining_balance,
            'interest_rate'     => $this->interest_rate,
            'months_to_pay'     => $this->months_to_pay,
            'monthly_payment'   => $this->monthly_payment,
            'installments_paid' => $this->whenLoaded('payments', fn() => $this->payments->count()),
            'status'            => $this->status,
            'payments'          => $this->whenLoaded('payments', fn() => PaymentResource::collection($this->payments)),
            'created_at'        => $this->created_at,
        ];
    }
}
