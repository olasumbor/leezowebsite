<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrozenCargoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'frozen_cargo_id',
        'description',
        'quantity',
        'weight',
        'rate',
        'cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'float',
        'rate' => 'float',
        'cost' => 'float',
    ];

    public function frozenCargo(): BelongsTo
    {
        return $this->belongsTo(FrozenCargo::class);
    }
}
