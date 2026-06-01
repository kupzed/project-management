<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('project_materials')]
#[Fillable([
    'project_id',
    'item_id',
    'warehouse_id',
    'stock_movement_id',
    'quantity',
    'allocated_at',
    'notes',
])]
class ProjectMaterial extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'allocated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function getActivityNameAttribute(): string
    {
        return 'Project Material #'.$this->id;
    }
}
