<?php

namespace App\Http\Resources\Investment;

use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'user_id'                => $this->user_id,
            'category'               => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'name'                   => $this->name,
            'type'                   => $this->type,
            'amount_invested'        => $this->amount_invested,
            'current_value'          => $this->current_value,
            'roi'                    => $this->roi,
            'roi_amount'             => $this->roi_amount,
            'purchase_date'          => $this->purchase_date,
            'description'            => $this->description,
            // new fields
            'total_value'            => $this->total_value,
            'period'                 => $this->period,
            'months_of_payment'      => $this->months_of_payment,
            'amount_per_payment'     => $this->amount_per_payment,
            'date_started'           => $this->date_started,
            'other_investment_title' => $this->other_investment_title,
            'payment_status'         => $this->payment_status ?? 'active',
            'total_paid'             => $this->total_paid,
            'created_at'             => $this->created_at,
        ];
    }
}
