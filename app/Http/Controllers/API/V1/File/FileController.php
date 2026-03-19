<?php

namespace App\Http\Controllers\API\V1\File;

use App\Http\Controllers\Controller;
use App\Http\Resources\File\FileResource;
use App\Models\File;
use App\Services\FileService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private FileService $service) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'fileable_type' => ['required', 'string'],
            'fileable_id' => ['required', 'integer'],
        ]);

        $files = $this->service->getByMorphable($request->fileable_type, (int) $request->fileable_id);
        return $this->respondSuccess(FileResource::collection($files));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'fileable_type' => ['required', 'string'],
            'fileable_id' => ['required', 'integer'],
        ]);

        $file = $this->service->store(
            auth()->user(),
            $request->file('file'),
            $request->fileable_type,
            (int) $request->fileable_id
        );

        return $this->respondCreated(new FileResource($file), 'File uploaded successfully');
    }

    public function destroy(File $file): JsonResponse
    {
        abort_if($file->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($file);
        return $this->respondSuccess(null, 'File deleted successfully');
    }

    public function download(File $file)
    {
        abort_if($file->user_id !== auth()->id(), 403, 'Unauthorized');
        return Storage::disk('public')->download($file->path, $file->original_name);
    }
}
