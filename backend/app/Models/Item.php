<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('items')]
#[Fillable([
    'sku',
    'category_id',
    'name',
    'unit',
    'minimum_stock',
])]
#[Appends(['attachments'])]
class Item extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function projectMaterials(): HasMany
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    public function itemAttachments(): HasMany
    {
        return $this->hasMany(ItemAttachment::class);
    }

    public function getActivityNameAttribute(): string
    {
        return $this->name ?? 'Item #'.$this->id;
    }

    public function getAttachmentsAttribute(): array
    {
        $atts = $this->getRelationValue('itemAttachments') ?? $this->itemAttachments()->get();

        if ($atts && $atts->count() > 0) {
            return $atts->map(fn ($att) => [
                'id'          => $att->id,
                'name'        => $att->name ?: basename($att->file_path),
                'description' => $att->description,
                'size'        => $att->size,
                'sizeLabel'   => $att->size ? $this->formatBytes($att->size) : null,
                'path'        => $att->file_path,
                'url'         => asset('storage/'.$att->file_path),
            ])->all();
        }

        return [];
    }

    protected function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null) return null;
        $units = ['bytes','KB','MB','GB','TB'];
        $i = 0; $num = (float) $bytes;
        while ($num >= 1024 && $i < count($units) - 1) { $num /= 1024; $i++; }
        $rounded = ($i === 0) ? round($num) : ($num < 10 ? number_format($num, 1) : (string) round($num));
        return $rounded . $units[$i];
    }
}
