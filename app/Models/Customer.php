<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kategori',
        'alamat',
        'website',
        'email',
        'kontak_1',
        'kontak_1_nama',
        'kontak_1_jabatan',
        'kontak_2_nama',
        'kontak_2',
        'kontak_2_jabatan',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
} 