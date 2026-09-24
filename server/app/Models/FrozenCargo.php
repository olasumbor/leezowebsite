<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'cost',
        'total_cost',
        'invoice_generated',
    ];

    protected $casts = [
        'invoice_generated' => 'boolean',
        'cost' => 'float',
        'total_cost' => 'float',
        'weight' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FrozenCargoItem::class);
    }

    /**
     * Recalculate the cached total from items (cost or quantity*rate),
     * falling back to the header-level cost for legacy records.
     */
    public function calculateAndUpdateTotals(): void
    {
        $items = $this->items()->get();

        $total = 0;
        $hasPricedItem = false;

        foreach ($items as $item) {
            if ($item->cost !== null && is_numeric($item->cost)) {
                $total += (float) $item->cost;
                $hasPricedItem = true;
            } elseif (
                $item->quantity !== null && $item->rate !== null
                && is_numeric($item->quantity) && is_numeric($item->rate)
            ) {
                $total += (float) $item->quantity * (float) $item->rate;
                $hasPricedItem = true;
            }
        }

        $resolved = $hasPricedItem ? $total : (is_numeric($this->cost) ? (float) $this->cost : 0);

        $this->update([
            'total_cost' => $resolved,
            // Keep legacy header cost in sync when items carry the pricing
            'cost' => $hasPricedItem ? $resolved : $this->cost,
        ]);
    }

    public function frozenTotal(): float
    {
        if (is_numeric($this->total_cost) && (float) $this->total_cost > 0) {
            return (float) $this->total_cost;
        }

        if ($this->relationLoaded('items') && $this->items->count() > 0) {
            $total = 0;
            foreach ($this->items as $item) {
                if ($item->cost !== null && is_numeric($item->cost)) {
                    $total += (float) $item->cost;
                } elseif (
                    $item->quantity !== null && $item->rate !== null
                    && is_numeric($item->quantity) && is_numeric($item->rate)
                ) {
                    $total += (float) $item->quantity * (float) $item->rate;
                }
            }
            if ($total > 0) {
                return $total;
            }
        }

        return is_numeric($this->cost) ? (float) $this->cost : 0;
    }
}

