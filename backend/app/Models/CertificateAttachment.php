<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'certificate_id',
    'name',
    'description',
    'file_path',
    'mime',
    'size',
])]
#[Appends(['url'])]
class CertificateAttachment extends Model
{
    use HasFactory, LogsActivity;

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function getActivityName(): string
    {
        return $this->name ?: 'Certificate Attachment #' . $this->id;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
