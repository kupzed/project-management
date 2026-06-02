<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query->where('type', $this->input('type')))
                    ->ignore($this->category()?->id),
            ],
            'type' => ['required', Rule::in(['item', 'project', 'activity', 'certificate'])],
        ];
    }

    private function category(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }
}
