<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Color extends Model
{
    use HasFactory;

    protected $fillable = ['year_id', 'name', 'hex_code'];

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }
}
