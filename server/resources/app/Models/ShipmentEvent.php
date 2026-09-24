<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'location',
        'description',
        'timestamp',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
