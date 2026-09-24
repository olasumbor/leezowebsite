<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'description',
        'category',
        'supplier',
        'quantity',
        'weight',
        'rate',
        'cost',
        'shipment_fee',
        'transportation',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'float',
        'rate' => 'float',
        'cost' => 'float',
        'shipment_fee' => 'float',
        'transportation' => 'float',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }
}