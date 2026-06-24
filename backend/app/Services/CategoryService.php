<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function getPaginatedCategories(array $filters, int $perPage): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('items')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        if ($category->items()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Category cannot be deleted because it is still used by one or more items.',
            ]);
        }

        $category->delete();
    }
}
