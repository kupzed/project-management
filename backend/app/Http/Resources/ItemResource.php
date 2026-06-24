<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'name' => $this->name,
            'unit' => $this->unit,
            'minimum_stock' => $this->minimum_stock,
            'inventories' => $this->whenLoaded('inventories', function () {
                return $this->inventories->map(function ($inventory) {
                    return [
                        'id' => $inventory->id,
                        'warehouse_id' => $inventory->warehouse_id,
                        'warehouse' => $inventory->relationLoaded('warehouse') && $inventory->warehouse ? [
                            'id' => $inventory->warehouse->id,
                            'name' => $inventory->warehouse->name,
                        ] : null,
                        'quantity' => $inventory->quantity,
                        'placement' => $inventory->placement,
                    ];
                });
            }),
            'attachments' => $this->attachments,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
