<?php

namespace App\Http\Resources\File;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'url' => Storage::disk('public')->url($this->path),
            'created_at' => $this->created_at,
        ];
    }
}
