<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Procurement;
use App\Models\Shipment;
use App\Models\Quote;
use App\Models\PickupDelivery;
use App\Models\FrozenCargo;

class AdminController extends Controller
{
    public function overview()
    {
        $usersCount = User::count();
        $procurementsCount = Procurement::count();
        $shipmentsCount = Shipment::count();
        $pickupsCount = PickupDelivery::count();
        $frozenCount = FrozenCargo::count();
        $quotesCount = Quote::count();

        $procurementRevenue = (float) Procurement::whereNotNull('cost')->sum('cost');
        $shipmentRevenue = (float) Shipment::whereNotNull('shipping_cost')->sum('shipping_cost');
        $pickupRevenue = (float) PickupDelivery::whereNotNull('cost')->sum('cost');
        $frozenRevenue = (float) FrozenCargo::whereNotNull('cost')->sum('cost');
        $quoteRevenue = (float) Quote::whereNotNull('calculated_cost')->sum('calculated_cost');

        $totalRevenue = $procurementRevenue + $shipmentRevenue + $pickupRevenue + $frozenRevenue + $quoteRevenue;

        // Get recent activity (mix of recent records)
        $recentUsers = User::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => 'USR-' . $item->id,
                'type' => 'New User',
                'user' => $item->name,
                'status' => 'Active',
                'status_class' => 'completed',
                'created_at_ts' => $item->created_at ? $item->created_at->timestamp : 0,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : 'N/A'
            ];
        });

        $recentProcurements = Procurement::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->procurement_id ?: ('PROC-' . $item->id),
                'type' => 'Procurement',
                'user' => $item->name ?: ($item->user ? $item->user->name : 'N/A'),
                'status' => ucfirst($item->status ?: 'Pending'),
                'status_class' => strtolower($item->status) === 'completed' ? 'completed' : 'pending',
                'created_at_ts' => $item->created_at ? $item->created_at->timestamp : 0,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : 'N/A'
            ];
        });

        $recentShipments = Shipment::with('user')->orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->tracking_id ?: ('SHP-' . $item->id),
                'type' => 'Shipment',
                'user' => $item->user ? $item->user->name : 'N/A',
                'status' => ucfirst($item->status ?: 'Pending'),
                'status_class' => strtolower($item->status) === 'delivered' ? 'completed' : 'pending',
                'created_at_ts' => $item->created_at ? $item->created_at->timestamp : 0,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : 'N/A'
            ];
        });

        $recentPickups = PickupDelivery::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->request_id ?: ('PKD-' . $item->id),
                'type' => 'Pick & Delivery',
                'user' => $item->name ?: ($item->user ? $item->user->name : 'N/A'),
                'status' => ucfirst($item->status ?: 'Pending'),
                'status_class' => strtolower($item->status) === 'completed' ? 'completed' : 'pending',
                'created_at_ts' => $item->created_at ? $item->created_at->timestamp : 0,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : 'N/A'
            ];
        });

        $recentFrozen = FrozenCargo::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->request_id ?: ('FRZ-' . $item->id),
                'type' => 'Frozen Cargo',
                'user' => $item->name ?: ($item->user ? $item->user->name : 'N/A'),
                'status' => ucfirst($item->status ?: 'Pending'),
                'status_class' => strtolower($item->status) === 'completed' ? 'completed' : 'pending',
                'created_at_ts' => $item->created_at ? $item->created_at->timestamp : 0,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : 'N/A'
            ];
        });

        $activity = $recentUsers
            ->concat($recentProcurements)
            ->concat($recentShipments)
            ->concat($recentPickups)
            ->concat($recentFrozen)
            ->sortByDesc('created_at_ts')
            ->values()
            ->take(6);

        return response()->json([
            'users_count' => $usersCount,
            'procurements_count' => $procurementsCount,
            'shipments_count' => $shipmentsCount,
            'pickups_count' => $pickupsCount,
            'frozen_count' => $frozenCount,
            'quotes_count' => $quotesCount,
            'total_revenue' => $totalRevenue,
            'activity' => $activity
        ]);
    }
}

