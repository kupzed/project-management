<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('categories')]
#[Fillable([
    'name',
    'type',
])]
class Category extends Model
{
    use HasFactory, LogsActivity;

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function getActivityNameAttribute(): string
    {
        return $this->name ?? 'Category #'.$this->id;
    }
}
