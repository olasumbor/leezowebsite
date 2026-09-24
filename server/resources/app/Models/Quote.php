<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'shipping_type',
        'origin_country',
        'destination_country',
        'weight',
        'height',
        'width',
        'length',
        'shipping_details',
        'calculated_cost',
        'status',
    ];
}
