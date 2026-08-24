<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'user_id',
        'name',
        'email',
        'phone',
        'details',
        'status',
        'category',
        'quantity',
        'supplier',
        'location',
        'expected_date',
        'delivered_date',
        'recipient_location',
        'cost',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
