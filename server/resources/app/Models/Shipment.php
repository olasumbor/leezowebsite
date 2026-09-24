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
        'shipment_type',
        'weight',
        'packages',
        'shipped_date',
        'delivered_date',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'recipient_location',
        'shipping_cost',
        'invoice_generated',
    ];

    protected $casts = [
        'invoice_generated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('timestamp', 'desc');
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
