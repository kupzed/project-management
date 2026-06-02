<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('inventories')]
#[Fillable([
    'item_id',
    'warehouse_id',
    'quantity',
])]
class Inventory extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getActivityNameAttribute(): string
    {
        return 'Inventory #'.$this->id;
    }
}
