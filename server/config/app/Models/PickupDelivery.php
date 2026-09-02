<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'name',
        'email',
        'phone',
        'pickup_address',
        'delivery_address',
        'delivery_phone',
        'status',
        'cost',
        'invoice_generated',
    ];

    protected $casts = [
        'invoice_generated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
