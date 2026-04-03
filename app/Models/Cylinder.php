<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cylinder extends Model
{
    use HasFactory;

    protected $fillable = ['model_id', 'count'];

    public function model(): BelongsTo
    {
        return $this->belongsTo(Model::class);
    }
}
