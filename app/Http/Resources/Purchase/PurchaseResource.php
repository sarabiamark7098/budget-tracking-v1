<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'user_id'                => $this->user_id,
            'item_name'              => $this->item_name,
            'total_cost'             => $this->total_cost,
            'payment_method'         => $this->payment_method,
            'is_installment'         => $this->is_installment,
            'installment_count'      => $this->installment_count,
            'installment_amount'     => $this->installment_amount,
            'installments_paid'      => $this->installments_paid,
            'remaining_installments' => $this->remaining_installments,
            'amount_paid'            => $this->amount_paid,
            'remaining_balance'      => $this->remaining_balance,
            'purchase_date'          => $this->purchase_date,
            'created_at'             => $this->created_at,
            // Payment history (each "Pay Month" click)
            'payments'               => $this->whenLoaded('payments', fn() =>
                $this->payments->map(fn($p) => [
                    'id'                 => $p->id,
                    'installment_number' => $p->installment_number,
                    'amount'             => (float) $p->amount,
                    'paid_at'            => $p->paid_at?->toDateString(),
                ])->values()
            ),
        ];
    }
}
