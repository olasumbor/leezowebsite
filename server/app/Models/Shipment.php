<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_id',
        'user_id',
        'origin',
        'destination',
        'status',
        'expected_delivery_date',
        'service',
        'weight',
        'packages',
        'shipped_date',
        'delivered_date',
        'recipient_name',
        'recipient_location',
        'shipping_cost',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('timestamp', 'desc');
    }
}
