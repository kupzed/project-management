<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('stock_movements')]
#[Fillable([
    'type',
    'item_id',
    'source_warehouse_id',
    'destination_warehouse_id',
    'project_id',
    'quantity',
    'notes',
    'occurred_at',
    'placement',
])]
class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMaterial(): HasOne
    {
        return $this->hasOne(ProjectMaterial::class);
    }

    public function getActivityNameAttribute(): string
    {
        return 'Stock Movement #'.$this->id;
    }
}
