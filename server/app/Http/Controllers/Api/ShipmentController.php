<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ShipmentCreatedMail;
use App\Mail\ShipmentStatusUpdatedMail;

class ShipmentController extends Controller
{
    // Public track shipment
    public function track($tracking_id)
    {
        $shipment = Shipment::with('events')->where('tracking_id', $tracking_id)->first();

        if (!$shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        return response()->json($shipment);
    }

    // Get user's shipments (Requires Auth)
    public function index(Request $request)
    {
        $shipments = Shipment::where('user_id', $request->user()->id)->get();
        return response()->json($shipments);
    }

    // Get user's shipment stats (Requires Auth)
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $total = Shipment::where('user_id', $userId)->count();
        $delivered = Shipment::where('user_id', $userId)->where('status', 'delivered')->count();
        $pending = Shipment::where('user_id', $userId)->where('status', 'pending')->count();
        $inTransit = Shipment::where('user_id', $userId)->where('status', 'in_transit')->count();

        return response()->json([
            'total' => $total,
            'delivered' => $delivered,
            'pending' => $pending,
            'in_transit' => $inTransit,
        ]);
    }

    // Get single shipment details (Requires Auth)
    public function show(Request $request, $id)
    {
        $shipment = Shipment::with('events')
            ->where('user_id', $request->user()->id)
            ->where('tracking_id', $id)
            ->firstOrFail();

        return response()->json($shipment);
    }

    // Download Shipment Receipt
    public function downloadReceipt(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $shipment = $this->findShipment($id);
        } else {
            $query = Shipment::with(['user', 'events'])->where('user_id', $user->id);
            if (is_numeric($id)) {
                $shipment = $query->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('tracking_id', (string) $id);
                })->firstOrFail();
            } else {
                $shipment = $query->where('tracking_id', $id)->firstOrFail();
            }
        }

        return response()->view('receipts.shipment-receipt', [
            'shipment' => $shipment
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }

    // Download Shipment Invoice
    public function downloadInvoice(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $shipment = $this->findShipment($id);
        } else {
            $query = Shipment::with(['user', 'events'])->where('user_id', $user->id);
            if (is_numeric($id)) {
                $shipment = $query->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('tracking_id', (string) $id);
                })->firstOrFail();
            } else {
                $shipment = $query->where('tracking_id', $id)->firstOrFail();
            }
        }

        $cost = is_numeric($shipment->shipping_cost) ? (float)$shipment->shipping_cost : 450000;

        $items = [
            [
                'name' => 'Shipment Cargo (' . ($shipment->service ?? 'Air Freight') . ') - ' . ($shipment->origin ?? 'Origin') . ' to ' . ($shipment->destination ?? 'Destination'),
                'amount' => $cost,
                'subtext' => ($shipment->packages ?? 1) . '.00 x ' . number_format($cost / max(1, $shipment->packages ?? 1), 2)
            ]
        ];

        return response()->view('invoices.invoice', [
            'invoice_number' => 'INV-' . strtoupper(substr(md5($shipment->tracking_id ?? $id), 0, 6)),
            'customer_name' => $shipment->recipient ?? ($shipment->user->name ?? 'Customer'),
            'invoice_date' => $shipment->created_at ? $shipment->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $shipment->expected_delivery_date ? \Carbon\Carbon::parse($shipment->expected_delivery_date)->format('d M Y') : date('d M Y'),
            'items' => $items,
            'total_amount' => $cost,
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }

    private function findShipment($id)
    {
        $query = Shipment::with(['user', 'events']);
        if (is_numeric($id)) {
            return $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->firstOrFail();
        }
        return $query->where('tracking_id', $id)->firstOrFail();
    }

    // Admin: List all shipments
    public function adminIndex()
    {
        $shipments = Shipment::with(['user', 'events'])->get();
        return response()->json($shipments);
    }

    // Admin: Get single shipment
    public function adminShow($id)
    {
        $shipment = $this->findShipment($id);
        return response()->json($shipment);
    }

    // Admin: Create shipment
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'expected_delivery_date' => 'nullable|date',
        ]);

        $trackingId = 'LEEZO' . mt_rand(100000, 9999999);

        $shipment = Shipment::create([
            'tracking_id' => $trackingId,
            'user_id' => $request->user_id,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'service' => $request->service ?? 'Air Freight',
            'weight' => $request->weight,
            'packages' => $request->packages ?? 1,
            'recipient_name' => $request->recipient ?? $request->recipient_name,
            'recipient_location' => $request->recipient_location ?? $request->destination,
            'shipping_cost' => $request->shipping_cost,
            'status' => 'pending',
            'expected_delivery_date' => $request->expected_delivery_date,
        ]);

        // Create initial tracking event
        $shipment->events()->create([
            'location' => $request->origin,
            'description' => 'Shipment information received and created by admin.',
        ]);

        $shipment->load('user');
        if ($shipment->user && $shipment->user->email) {
            try {
                Mail::to($shipment->user->email)->send(new ShipmentCreatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentCreatedMail to {$shipment->user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => $shipment
        ], 201);
    }

    // Admin: Update full shipment
    public function adminUpdate(Request $request, $id)
    {
        $shipment = $this->findShipment($id);

        $data = $request->only([
            'status',
            'origin',
            'destination',
            'service',
            'weight',
            'packages',
            'recipient_name',
            'recipient_location',
            'shipping_cost',
            'expected_delivery_date',
            'delivered_date',
        ]);
        if ($request->has('recipient') && empty($data['recipient_name'])) {
            $data['recipient_name'] = $request->recipient;
        }

        $shipment->update($data);

        if ($shipment->user && $shipment->user->email) {
            try {
                Mail::to($shipment->user->email)->send(new ShipmentStatusUpdatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$shipment->user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Shipment updated successfully',
            'shipment' => $shipment
        ]);
    }

    // Admin: Update shipment status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $shipment = $this->findShipment($id);
        $shipment->status = $request->status;
        $shipment->save();

        if ($shipment->user && $shipment->user->email) {
            try {
                Mail::to($shipment->user->email)->send(new ShipmentStatusUpdatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$shipment->user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Shipment status updated',
            'shipment' => $shipment
        ]);
    }

    // Admin: Add tracking event
    public function addEvent(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        $shipment = $this->findShipment($id);

        $event = $shipment->events()->create([
            'location' => $request->location ?? $shipment->origin,
            'description' => $request->description,
        ]);

        if ($shipment->user && $shipment->user->email) {
            try {
                Mail::to($shipment->user->email)->send(new ShipmentStatusUpdatedMail($shipment, $event));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$shipment->user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Tracking event added',
            'event' => $event
        ]);
    }
}


