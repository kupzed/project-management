<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService)
    {
        $this->middleware('permission:category-view')->only(['index', 'show']);
        $this->middleware('permission:category-create')->only(['store']);
        $this->middleware('permission:category-update')->only(['update']);
        $this->middleware('permission:category-delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $categories = $this->categoryService->getPaginatedCategories(
            $request->all(),
            $this->resolvePerPage($request)
        );

        return CategoryResource::collection($categories)->additional([
            'message' => 'Categories retrieved successfully',
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->categoryService->createCategory($request->validated());

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function show(Category $category)
    {
        return (new CategoryResource($category))->additional([
            'message' => 'Category retrieved successfully',
        ]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category = $this->categoryService->updateCategory($category, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category)
    {
        $this->categoryService->deleteCategory($category);

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
