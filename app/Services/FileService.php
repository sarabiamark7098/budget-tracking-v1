<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function store(User $user, UploadedFile $file, string $morphType, int $morphId): File
    {
        $originalName = $file->getClientOriginalName();
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/' . $morphType, $storedName, 'public');

        return File::create([
            'user_id' => $user->id,
            'fileable_type' => $morphType,
            'fileable_id' => $morphId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function delete(File $file): bool
    {
        Storage::disk('public')->delete($file->path);
        return $file->delete();
    }

    public function getByMorphable(string $morphType, int $morphId)
    {
        return File::where('fileable_type', $morphType)
            ->where('fileable_id', $morphId)
            ->get();
    }
}
