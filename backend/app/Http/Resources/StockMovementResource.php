<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn () => new ItemResource($this->item)),
            'source_warehouse_id' => $this->source_warehouse_id,
            'source_warehouse' => $this->whenLoaded('sourceWarehouse', function () {
                return $this->sourceWarehouse ? [
                    'id' => $this->sourceWarehouse->id,
                    'name' => $this->sourceWarehouse->name,
                    'location' => $this->sourceWarehouse->location,
                ] : null;
            }),
            'destination_warehouse_id' => $this->destination_warehouse_id,
            'destination_warehouse' => $this->whenLoaded('destinationWarehouse', function () {
                return $this->destinationWarehouse ? [
                    'id' => $this->destinationWarehouse->id,
                    'name' => $this->destinationWarehouse->name,
                    'location' => $this->destinationWarehouse->location,
                ] : null;
            }),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', function () {
                return $this->project ? [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                ] : null;
            }),
            'project_material' => $this->whenLoaded('projectMaterial', function () {
                return $this->projectMaterial ? [
                    'id' => $this->projectMaterial->id,
                    'quantity' => $this->projectMaterial->quantity,
                    'allocated_at' => $this->projectMaterial->allocated_at?->format('Y-m-d H:i:s'),
                    'notes' => $this->projectMaterial->notes,
                ] : null;
            }),
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
