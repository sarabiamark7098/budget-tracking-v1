<?php

namespace App\Http\Controllers\API\V1\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CategoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->service->getAll(auth()->user());
        return $this->respondSuccess(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new CategoryResource($category), 'Category created successfully');
    }

    public function show(Category $category): JsonResponse
    {
        abort_if($category->user_id !== null && $category->user_id !== auth()->id(), 403, 'Unauthorized');
        return $this->respondSuccess(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        abort_if($category->user_id !== auth()->id(), 403, 'Unauthorized');
        $category = $this->service->update($category, $request->validated());
        return $this->respondSuccess(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_if($category->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($category);
        return $this->respondSuccess(null, 'Category deleted successfully');
    }
}
