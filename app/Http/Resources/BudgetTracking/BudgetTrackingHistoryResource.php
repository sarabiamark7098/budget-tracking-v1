<?php

namespace App\Http\Resources\BudgetTracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetTrackingHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'action'       => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id'   => $this->subject_id,
            'old_values'   => $this->old_values,
            'new_values'   => $this->new_values,
            'description'  => $this->description,
            'changed_by'   => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
