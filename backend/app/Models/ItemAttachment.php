<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_id',
    'name',
    'description',
    'file_path',
    'mime',
    'size',
])]
#[Appends(['url'])]
class ItemAttachment extends Model
{
    use HasFactory, LogsActivity;

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getActivityName(): string
    {
        return $this->name ?: 'Item Attachment #' . $this->id;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
