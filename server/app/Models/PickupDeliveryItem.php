<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_delivery_id',
        'description',
        'cost',
    ];

    protected $casts = [
        'cost' => 'float',
    ];

    public function pickupDelivery(): BelongsTo
    {
        return $this->belongsTo(PickupDelivery::class);
    }
}
