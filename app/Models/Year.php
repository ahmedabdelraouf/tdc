<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Year extends Model
{
    use HasFactory;

    protected $fillable = ['model_id', 'year'];

    public function model(): BelongsTo
    {
        return $this->belongsTo(Model::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }
}
