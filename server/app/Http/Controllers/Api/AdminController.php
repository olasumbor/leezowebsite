<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Procurement;
use App\Models\Shipment;
use App\Models\Quote;

class AdminController extends Controller
{
    public function overview()
    {
        $usersCount = User::count();
        $procurementsCount = Procurement::count();
        $shipmentsCount = Shipment::count();
        $quotesCount = Quote::count();

        // Get recent activity (mix of recent records)
        $recentUsers = User::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => 'USR-' . $item->id,
                'type' => 'New User',
                'user' => $item->name,
                'status' => 'Active',
                'status_class' => 'completed',
                'date' => $item->created_at->diffForHumans()
            ];
        });

        $recentProcurements = Procurement::orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->id,
                'type' => 'Procurement',
                'user' => $item->name,
                'status' => ucfirst($item->status),
                'status_class' => strtolower($item->status) === 'completed' ? 'completed' : 'pending',
                'date' => $item->created_at->diffForHumans()
            ];
        });

        $recentShipments = Shipment::with('user')->orderBy('created_at', 'desc')->take(2)->get()->map(function($item) {
            return [
                'id' => $item->tracking_number,
                'type' => 'Shipment',
                'user' => $item->user ? $item->user->name : 'N/A',
                'status' => ucfirst($item->status),
                'status_class' => strtolower($item->status) === 'delivered' ? 'completed' : 'pending',
                'date' => $item->created_at->diffForHumans()
            ];
        });

        $activity = $recentUsers->concat($recentProcurements)->concat($recentShipments)
            ->sortByDesc('date')
            ->values()
            ->take(5);

        return response()->json([
            'users_count' => $usersCount,
            'procurements_count' => $procurementsCount,
            'shipments_count' => $shipmentsCount,
            'quotes_count' => $quotesCount,
            'activity' => $activity
        ]);
    }
}
