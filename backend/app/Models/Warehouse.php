<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('warehouses')]
#[Fillable([
    'name',
    'location',
])]
class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function sourceStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'source_warehouse_id');
    }

    public function destinationStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'destination_warehouse_id');
    }

    public function projectMaterials(): HasMany
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    public function getActivityNameAttribute(): string
    {
        return $this->name ?? 'Warehouse #'.$this->id;
    }
}
