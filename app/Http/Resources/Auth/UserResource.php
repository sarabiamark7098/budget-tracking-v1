<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'token' => $this->when(isset($this->token), $this->token),
        ];
    }
}
