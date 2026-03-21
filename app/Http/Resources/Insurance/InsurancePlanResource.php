<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'provider_name'     => $this->provider_name,
            'plan_name'         => $this->plan_name,
            'coverage_type'     => $this->coverage_type ?? [],
            'coverage_amount'   => $this->coverage_amount,
            'premium_amount'    => $this->premium_amount,
            'payment_frequency' => $this->payment_frequency,
            'policy_number'     => $this->policy_number,
            'notes'             => $this->notes,
            'total_paid'        => $this->whenLoaded('insurancePayments', fn() => round($this->insurancePayments->sum('amount'), 2)),
            'created_at'        => $this->created_at,
        ];
    }
}
