<?php

namespace App\Http\Resources\BudgetTracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'currency'        => $this->currency,
            'period'          => $this->period,
            'start_date'      => $this->start_date,
            'end_date'        => $this->end_date,
            'status'          => $this->status,
            // join_code shown only to members (they are authenticated and authorized)
            'join_code'       => $this->join_code,
            'is_owner'        => $userId ? $this->isOwner($userId) : false,
            'total_allocated'   => $this->total_allocated,
            'total_income'      => $this->total_income,
            'total_expense'     => $this->total_expense,
            'balance'           => $this->balance,
            'available_balance' => $this->available_balance,
            'owner'           => $this->whenLoaded('owner', fn() => [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'members'     => BudgetTrackingMemberResource::collection($this->whenLoaded('members')),
            'allocations' => BudgetTrackingAllocationResource::collection($this->whenLoaded('allocations')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
