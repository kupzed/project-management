<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'inventories_count' => $this->whenCounted('inventories'),
            'inventories' => $this->whenLoaded('inventories', function () {
                return $this->inventories->map(function ($inventory) {
                    return [
                        'id' => $inventory->id,
                        'item_id' => $inventory->item_id,
                        'warehouse_id' => $inventory->warehouse_id,
                        'quantity' => $inventory->quantity,
                        'placement' => $inventory->placement,
                        'item' => $inventory->relationLoaded('item') && $inventory->item ? [
                            'id' => $inventory->item->id,
                            'sku' => $inventory->item->sku,
                            'name' => $inventory->item->name,
                            'unit' => $inventory->item->unit,
                            'category' => $inventory->item->relationLoaded('category') && $inventory->item->category ? [
                                'id' => $inventory->item->category->id,
                                'name' => $inventory->item->category->name,
                                'type' => $inventory->item->category->type,
                            ] : null,
                        ] : null,
                    ];
                });
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
