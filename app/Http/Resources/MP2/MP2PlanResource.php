<?php

namespace App\Http\Resources\MP2;

use Illuminate\Http\Resources\Json\JsonResource;

class MP2PlanResource extends JsonResource
{
    public function toArray($request): array
    {
        $calculation = $this->calculateProjectedEarnings();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'monthly_contribution' => $this->monthly_contribution,
            'duration_years' => $this->duration_years,
            'start_date' => $this->start_date,
            'projected_earnings' => $this->projected_earnings,
            'total_contributions' => $this->total_contributions,
            'notes' => $this->notes,
            'calculation_breakdown' => $calculation,
            'created_at' => $this->created_at,
        ];
    }
}
