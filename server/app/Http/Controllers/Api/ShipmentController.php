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
use App\Mail\InvoiceGeneratedMail;

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
        $user = $request->user();
        if ($user && $user->name) {
            Shipment::whereNull('user_id')
                ->where('recipient_name', $user->name)
                ->update(['user_id' => $user->id]);
        }

        $shipments = Shipment::where('user_id', $user->id)->get();
        return response()->json($shipments);
    }

    // Get user's shipment stats (Requires Auth)
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $total = Shipment::where('user_id', $userId)->count();
        $delivered = Shipment::where('user_id', $userId)->whereIn('status', ['delivered', 'Delivered', 'DELIVERED'])->count();
        $pending = Shipment::where('user_id', $userId)->whereIn('status', ['pending', 'Pending', 'PENDING'])->count();
        $inTransit = Shipment::where('user_id', $userId)->whereIn('status', ['in_transit', 'In Transit', 'IN_TRANSIT', 'in-transit'])->count();

        return response()->json([
            'total' => $total,
            'delivered' => $delivered,
            'pending' => $pending,
            'in_transit' => $inTransit,
        ]);
    }

    // Get single shipment details (Public/Auth)
    public function show(Request $request, $id)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            Shipment::whereNull('user_id')
                ->where(function ($q) use ($user) {
                    if ($user->name) $q->where('recipient_name', $user->name);
                    if ($user->email) $q->orWhere('recipient_name', $user->email);
                })
                ->update(['user_id' => $user->id]);
        }

        $query = Shipment::with('events');

        if ($user && $user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('recipient_name', $user->name)
                  ->orWhere('recipient_name', $user->email);
            });
        }

        $shipment = $query->where(function ($q) use ($id) {
            $q->where('tracking_id', (string)$id)
              ->orWhere('id', $id);
        })->firstOrFail();

        return response()->json($shipment);
    }

    // Download Shipment Receipt
    public function downloadReceipt(Request $request, $id)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($user && $user->role === 'admin') {
            $shipment = $this->findShipment($id);
        } else {
            if ($user && ($user->name || $user->email)) {
                Shipment::whereNull('user_id')
                    ->where(function ($q) use ($user) {
                        if ($user->name) $q->where('recipient_name', $user->name);
                        if ($user->email) $q->orWhere('recipient_name', $user->email);
                    })
                    ->update(['user_id' => $user->id]);
            }
            $query = Shipment::with(['user', 'events']);
            if ($user) {
                $query->where('user_id', $user->id);
            }
            $shipment = $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->firstOrFail();
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
        $user = $request->user('sanctum') ?? $request->user();

        if ($user && $user->role === 'admin') {
            $shipment = $this->findShipment($id);
        } else {
            if ($user && ($user->name || $user->email)) {
                Shipment::whereNull('user_id')
                    ->where(function ($q) use ($user) {
                        if ($user->name) $q->where('recipient_name', $user->name);
                        if ($user->email) $q->orWhere('recipient_name', $user->email);
                    })
                    ->update(['user_id' => $user->id]);
            }
            $query = Shipment::with(['user', 'events']);
            if ($user) {
                $query->where('user_id', $user->id);
            }
            $shipment = $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->firstOrFail();
        }

        if (!$shipment->invoice_generated && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Invoice has not been generated by admin yet.'], 403);
        }

        $cost = is_numeric($shipment->shipping_cost) ? (float)$shipment->shipping_cost : 450000;

        $items = [
            [
                'name' => 'Shipment Cargo (' . ($shipment->service ?? 'Air Freight') . ') - ' . ($shipment->origin ?? 'Origin') . ' to ' . ($shipment->destination ?? 'Destination'),
                'amount' => $cost
            ]
        ];

        return response()->view('invoices.invoice', [
            'invoice_number' => 'INV-' . strtoupper(substr(md5($shipment->tracking_id ?? $id), 0, 6)),
            'customer_name' => $shipment->recipient ?? ($shipment->user->name ?? 'Customer'),
            'invoice_date' => $shipment->created_at ? $shipment->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $shipment->expected_delivery_date ? \Carbon\Carbon::parse($shipment->expected_delivery_date)->format('d M Y') : date('d M Y'),
            'items' => $items,
            'total_amount' => $cost,
            'bank_account_number' => \App\Models\Setting::get('bank_account_number', '0900779403'),
            'bank_account_name' => \App\Models\Setting::get('bank_account_name', 'Leezoe integrated'),
            'bank_name' => \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank.'),
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }

    // Admin: Generate Invoice
    public function generateInvoice(Request $request, $id)
    {
        $shipment = $this->findShipment($id);

        if (empty($shipment->shipping_cost) || !is_numeric($shipment->shipping_cost) || (float)$shipment->shipping_cost <= 0) {
            return response()->json([
                'message' => 'Cannot generate invoice: Shipment price (shipping cost) has not been updated yet. Please edit the shipment and set a price first.'
            ], 422);
        }

        $shipment->invoice_generated = true;
        $shipment->save();

        $recipientEmail = ($shipment->user ? $shipment->user->email : null) ?? ($shipment->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new InvoiceGeneratedMail($shipment, 'Shipment'));
            } catch (\Throwable $e) {
                Log::error("Failed to send InvoiceGeneratedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Shipment invoice generated successfully and notification email sent to customer.',
            'shipment' => $shipment
        ]);
    }

    private function findShipment($id)
    {
        return Shipment::with(['user', 'events'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->firstOrFail();
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

        $recipientEmail = ($shipment->user ? $shipment->user->email : null) ?? ($shipment->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ShipmentStatusUpdatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
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

        $recipientEmail = ($shipment->user ? $shipment->user->email : null) ?? ($shipment->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ShipmentStatusUpdatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
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

        $recipientEmail = ($shipment->user ? $shipment->user->email : null) ?? ($shipment->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ShipmentStatusUpdatedMail($shipment, $event));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Tracking event added',
            'event' => $event
        ]);
    }
}


