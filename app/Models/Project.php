<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
} 
