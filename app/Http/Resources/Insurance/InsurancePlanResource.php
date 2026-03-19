<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'provider_name' => $this->provider_name,
            'plan_name' => $this->plan_name,
            'coverage_type' => $this->coverage_type,
            'coverage_amount' => $this->coverage_amount,
            'premium_amount' => $this->premium_amount,
            'payment_frequency' => $this->payment_frequency,
            'next_payment_date' => $this->next_payment_date,
            'policy_number' => $this->policy_number,
            'description' => $this->description,
            'payments' => $this->whenLoaded('insurancePayments', fn() => InsurancePaymentResource::collection($this->insurancePayments)),
            'total_paid' => $this->whenLoaded('insurancePayments', fn() => $this->insurancePayments->sum('amount')),
            'created_at' => $this->created_at,
        ];
    }
}
