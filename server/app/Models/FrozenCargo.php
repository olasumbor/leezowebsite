<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrozenCargo extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'name',
        'email',
        'phone',
        'cargo_description',
        'temperature_requirement',
        'weight',
        'origin',
        'destination',
        'departure_date',
        'notes',
        'status',
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
