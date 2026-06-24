<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'sku')->ignore($this->item()?->id),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', 'item')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'integer', 'min:0'],

            // Attachment rules (optional)
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max per file
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_descriptions' => ['nullable', 'array'],
            'attachment_descriptions.*' => ['nullable', 'string'],

            // Existing attachment management (for updates)
            'removed_existing_ids' => ['nullable', 'array'],
            'removed_existing_ids.*' => ['integer'],
            'existing_attachment_ids' => ['nullable', 'array'],
            'existing_attachment_ids.*' => ['integer'],
            'existing_attachment_names' => ['nullable', 'array'],
            'existing_attachment_names.*' => ['nullable', 'string', 'max:255'],
            'existing_attachment_descriptions' => ['nullable', 'array'],
            'existing_attachment_descriptions.*' => ['nullable', 'string'],
        ];
    }

    private function item(): ?Item
    {
        $item = $this->route('item');

        return $item instanceof Item ? $item : null;
    }
}
