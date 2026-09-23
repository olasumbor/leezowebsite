<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShipmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ShipmentCreatedMail;
use App\Mail\ShipmentStatusUpdatedMail;
use App\Mail\InvoiceGeneratedMail;
use App\Support\Pdf;

class ShipmentController extends Controller
{
    // Public track shipment
    public function track($tracking_id)
    {
        $shipment = Shipment::with(['events', 'items'])->where('tracking_id', $tracking_id)->first();

        if (!$shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        $this->hideItemPricing($shipment);

        return response()->json($shipment);
    }

    // Hide rate/cost from shipment items for contexts where pricing
    // should not be exposed (e.g. public tracking page).
    private function hideItemPricing($shipment)
    {
        if (!$shipment || !$shipment->relationLoaded('items')) {
            return;
        }

        $shipment->items->each(function ($item) {
            $item->makeHidden(['rate', 'cost']);
        });
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

        $shipments = Shipment::with('items')->where('user_id', $user->id)->get();
        $shipments->each(function ($s) {
            $s->can_edit = !$this->shipmentEditLocked($s);
        });
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

        $query = Shipment::with(['events', 'items']);

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

        if (!$user) {
            // Public / unauthenticated: hide pricing
            $this->hideItemPricing($shipment);
        } elseif ($user->role !== 'admin') {
            // Authenticated customer: show their own shipment pricing
            $shipment->can_edit = !$this->shipmentEditLocked($shipment);
        }

        return response()->json($shipment);
    }

    // A shipment is locked for user editing once the admin has generated
    // an invoice for it, or has set pricing (rate/cost) on its items.
    private function shipmentEditLocked($shipment)
    {
        if ($shipment->invoice_generated) {
            return true;
        }

        return $shipment->items->contains(function ($item) {
            return $item->rate !== null || $item->cost !== null;
        });
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
            $query = Shipment::with(['user', 'events', 'items']);
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

        $items = [];
        $totalAmount = $this->shipmentTotal($shipment);

        if (isset($shipment->items) && count($shipment->items) > 0) {
            foreach ($shipment->items as $item) {
                $items[] = [
                    'name' => $item->name ?? 'Shipment Item',
                    'quantity' => ($item->quantity !== null && $item->quantity !== '') ? (float) $item->quantity : null,
                    'weight' => ($item->weight !== null && $item->weight !== '') ? (float) $item->weight : null,
                    'rate' => ($item->rate !== null && $item->rate !== '') ? (float) $item->rate : null,
                    'cost' => ($item->cost !== null && $item->cost !== '') ? (float) $item->cost : null,
                ];
            }
        } else {
            $items = [
                [
                    'name' => 'Shipment Cargo (' . ($shipment->service ?? 'Air Freight') . ') - ' . ($shipment->origin ?? 'Origin') . ' to ' . ($shipment->destination ?? 'Destination'),
                    'quantity' => null,
                    'weight' => (isset($shipment->weight) && $shipment->weight !== '' && is_numeric($shipment->weight)) ? (float) $shipment->weight : null,
                    'rate' => null,
                    'cost' => $totalAmount,
                ]
            ];
        }

        $invoiceNumber = $shipment->tracking_id ?? $shipment->tracking_number ?? (string) $id;

        $pdf = Pdf::loadView('invoices.shipment-invoice', [
            'shipment' => $shipment,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $shipment->recipient_name ?? ($shipment->user->name ?? 'Customer'),
            'shipper_account' => $shipment->user->email ?? ($shipment->recipient_email ?? 'N/A'),
            'invoice_date' => $shipment->created_at ? $shipment->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $shipment->expected_delivery_date ? \Carbon\Carbon::parse($shipment->expected_delivery_date)->format('d M Y') : date('d M Y'),
            'items' => $items,
            'total_amount' => $totalAmount,
            'status' => $shipment->status ?? 'PENDING',
            'bank_account_number' => \App\Models\Setting::get('bank_account_number', '0900779403'),
            'bank_account_name' => \App\Models\Setting::get('bank_account_name', 'Leezoe integrated'),
            'bank_name' => \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank.'),
        ]);

        return $pdf->download('Shipment-Invoice-' . ($shipment->tracking_id ?? $shipment->tracking_number ?? 'unknown') . '.pdf');
    }

    // Admin: Generate Invoice
    public function generateInvoice(Request $request, $id)
    {
        $shipment = $this->findShipment($id);

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
        return Shipment::with(['user', 'events', 'items'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->firstOrFail();
    }

    // Admin: List all shipments
    public function adminIndex()
    {
        $shipments = Shipment::with(['user', 'events', 'items'])->get();
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
            'user_id' => 'nullable|exists:users,id',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'expected_delivery_date' => 'nullable|date',
            'shipped_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
        ]);

        $trackingId = 'LEEZO' . mt_rand(100000, 9999999);

        $user = \App\Models\User::find($request->user_id);

        $shipment = Shipment::create([
            'tracking_id' => $trackingId,
            'user_id' => $request->user_id,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'service' => $request->service ?? 'Air Freight',
            'shipment_type' => $request->shipment_type,
            'weight' => $request->weight,
            'packages' => $request->packages ?? 1,
            'recipient_name' => $request->recipient ?? $request->recipient_name ?? ($user->name ?? null),
            'recipient_email' => $request->recipient_email ?? ($user->email ?? null),
            'recipient_phone' => $request->recipient_phone,
            'recipient_location' => $request->recipient_location ?? $request->destination,
            'shipping_cost' => $request->shipping_cost,
            'status' => $request->status ?? 'pending',
            'expected_delivery_date' => $request->expected_delivery_date,
            'shipped_date' => $request->shipped_date,
            'delivered_date' => $request->delivered_date,
        ]);

        $this->syncShipmentItems($shipment, $request->items);

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

        $shipment->load('items');

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
            'shipment_type',
            'weight',
            'packages',
            'recipient_name',
            'recipient_email',
            'recipient_phone',
            'recipient_location',
            'shipping_cost',
            'expected_delivery_date',
            'shipped_date',
            'delivered_date',
        ]);
        if ($request->has('recipient') && empty($data['recipient_name'])) {
            $data['recipient_name'] = $request->recipient;
        }

        $shipment->update($data);

        $this->syncShipmentItems($shipment, $request->items);

        $recipientEmail = ($shipment->user ? $shipment->user->email : null) ?? ($shipment->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ShipmentStatusUpdatedMail($shipment));
            } catch (\Throwable $e) {
                Log::error("Failed to send ShipmentStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        $shipment->load('items');

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

    // User/Recipient: Create a shipment request (the recipient is the current user)
    public function userStore(Request $request)
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'expected_delivery_date' => 'nullable|date',
            'shipped_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
        ]);

        $user = $request->user();

        $trackingId = 'LEEZO' . mt_rand(100000, 9999999);

        $shipment = Shipment::create([
            'tracking_id' => $trackingId,
            'user_id' => $user->id,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'service' => $request->service ?? 'Air Freight',
            'shipment_type' => $request->shipment_type,
            'weight' => $request->weight,
            'packages' => $request->packages,
            // Recipient details come from the authenticated user account,
            // not from client-supplied form input
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'recipient_phone' => $user->phone,
            'recipient_location' => $request->destination,
            'shipping_cost' => $request->shipping_cost,
            'status' => 'pending',
            'expected_delivery_date' => $request->expected_delivery_date,
            'shipped_date' => $request->shipped_date,
            'delivered_date' => $request->delivered_date,
        ]);

        // Users must never set item pricing; rate/cost are admin-only
        $items = collect($request->items ?? [])->map(function ($item) {
            if (!is_array($item)) {
                return $item;
            }
            return [
                'name' => $item['name'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'weight' => $item['weight'] ?? null,
            ];
        })->all();

        $this->syncShipmentItems($shipment, $items);

        $shipment->events()->create([
            'location' => $request->origin,
            'description' => 'Shipment request received. Awaiting confirmation and booking by Leezofood.',
        ]);

        $shipment->load(['user', 'items']);

        $this->hideItemPricing($shipment);
        $shipment->can_edit = true;

        return response()->json([
            'message' => 'Shipment request submitted successfully. Tracking ID: ' . $trackingId,
            'shipment' => $shipment
        ], 201);
    }

    // User/Recipient: Update their own shipment request details.
    // Editing is only allowed while the shipment has not been priced
    // or invoiced by admin.
    public function userUpdate(Request $request, $id)
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'expected_delivery_date' => 'nullable|date',
            'shipped_date' => 'nullable|date',
        ]);

        $user = $request->user();

        $shipment = Shipment::with('items')
            ->where('user_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('tracking_id', (string) $id);
            })->first();

        if (!$shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        if ($this->shipmentEditLocked($shipment)) {
            return response()->json([
                'message' => 'This shipment can no longer be edited because it has been priced or invoiced by Leezofood. Please contact support for changes.'
            ], 403);
        }

        $shipment->update($request->only([
            'origin',
            'destination',
            'service',
            'shipment_type',
            'weight',
            'packages',
            'expected_delivery_date',
            'shipped_date',
        ]));

        $shipment->recipient_location = $shipment->destination;
        $shipment->save();

        // Users must never set item pricing; rate/cost are admin-only
        $items = collect($request->items ?? [])->map(function ($item) {
            if (!is_array($item)) {
                return $item;
            }
            return [
                'name' => $item['name'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'weight' => $item['weight'] ?? null,
            ];
        })->all();

        $this->syncShipmentItems($shipment, $items);

        $shipment->events()->create([
            'location' => $shipment->origin,
            'description' => 'Shipment details updated by customer.',
        ]);

        $shipment->load(['user', 'items']);

        $this->hideItemPricing($shipment);
        $shipment->can_edit = true;

        return response()->json([
            'message' => 'Shipment details updated successfully',
            'shipment' => $shipment
        ]);
    }

    // Normalize incoming items into a safe array of hashes
    private function normalizeItems($rawItems)
    {
        $items = [];

        if (empty($rawItems)) {
            return $items;
        }

        if (is_array($rawItems)) {
            foreach ($rawItems as $raw) {
                if (!is_array($raw)) {
                    continue;
                }
                $name = trim((string)($raw['name'] ?? ''));
                if (empty($name)) {
                    continue;
                }
                $items[] = [
                    'name' => $name,
                    'quantity' => empty($raw['quantity']) ? null : $raw['quantity'],
                    'weight' => empty($raw['weight']) ? null : $raw['weight'],
                    'rate' => empty($raw['rate']) ? null : $raw['rate'],
                    'cost' => empty($raw['cost']) ? null : $raw['cost'],
                ];
            }
        }

        return $items;
    }

    // Replace a shipment's items with the provided list
    private function syncShipmentItems($shipment, $rawItems)
    {
        $normalized = $this->normalizeItems($rawItems);

        if (!is_array($normalized)) {
            return;
        }

        // Remove previously stored items before storing the fresh set
        ShipmentItem::where('shipment_id', $shipment->id)->delete();

        foreach ($normalized as $item) {
            $shipment->items()->create($item);
        }
    }

    // Compute shipment total from its items, falling back to shipping_cost
    private function shipmentTotal($shipment)
    {
        $total = 0;

        if (isset($shipment->items) && count($shipment->items) > 0) {
            foreach ($shipment->items as $item) {
                if (!empty($item->cost) && is_numeric($item->cost)) {
                    $total += (float)$item->cost;
                } elseif (!empty($item->quantity) && !empty($item->rate)
                    && is_numeric($item->quantity) && is_numeric($item->rate)) {
                    $total += (float)$item->quantity * (float)$item->rate;
                }
            }
        }

        if ($total > 0) {
            return $total;
        }

        return is_numeric($shipment->shipping_cost) ? (float)$shipment->shipping_cost : (float)$shipment->shipping_cost;
    }
}


