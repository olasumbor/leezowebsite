<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'invoice_generated',
        'request_date',
        'expected_delivery',
        'delivery_date',
        'receipt_date',
        'total_cost',
        'total_shipment_fee',
        'total_transportation',
        'grand_total',
    ];

    protected $casts = [
        'invoice_generated' => 'boolean',
        'request_date' => 'date',
        'expected_delivery' => 'date',
        'delivery_date' => 'date',
        'receipt_date' => 'date',
        'cost' => 'float',
        'total_cost' => 'float',
        'total_shipment_fee' => 'float',
        'total_transportation' => 'float',
        'grand_total' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }

    /**
     * Calculate totals from items and update the procurement record
     */
    public function calculateAndUpdateTotals(): void
    {
        $items = $this->items;
        
        $totalCost = 0;
        $totalShipmentFee = 0;
        $totalTransportation = 0;

        foreach ($items as $item) {
            // Cost is the main item cost
            if ($item->cost && is_numeric($item->cost)) {
                $totalCost += (float) $item->cost;
            } elseif ($item->quantity && $item->rate && is_numeric($item->quantity) && is_numeric($item->rate)) {
                // Calculate cost from quantity * rate if not explicitly set
                $totalCost += (float) $item->quantity * (float) $item->rate;
            }

            // Sum shipment fees
            if ($item->shipment_fee && is_numeric($item->shipment_fee)) {
                $totalShipmentFee += (float) $item->shipment_fee;
            }

            // Sum transportation costs
            if ($item->transportation && is_numeric($item->transportation)) {
                $totalTransportation += (float) $item->transportation;
            }
        }

        $grandTotal = $totalCost + $totalShipmentFee + $totalTransportation;

        $this->update([
            'total_cost' => $totalCost,
            'total_shipment_fee' => $totalShipmentFee,
            'total_transportation' => $totalTransportation,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Get formatted totals for display
     */
    public function getFormattedTotalsAttribute()
    {
        return [
            'total_cost' => $this->total_cost ? "₦" . number_format($this->total_cost, 2) : "₦0.00",
            'total_shipment_fee' => $this->total_shipment_fee ? "₦" . number_format($this->total_shipment_fee, 2) : "₦0.00",
            'total_transportation' => $this->total_transportation ? "₦" . number_format($this->total_transportation, 2) : "₦0.00",
            'grand_total' => $this->grand_total ? "₦" . number_format($this->grand_total, 2) : "₦0.00",
        ];
    }
}
