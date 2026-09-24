<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupDelivery;
use App\Models\PickupDeliveryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PickupDeliveryCreatedMail;
use App\Mail\PickupDeliveryStatusUpdatedMail;
use App\Support\Pdf;

class PickupDeliveryController extends Controller
{
    // Create new pickup & delivery request (multi-item; cost is admin-only)
    public function store(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        // The dashboard form sends no sender identity fields: an authenticated
        // request is attached to (and filled from) the current user. Guests using
        // the public API directly must still provide name/email/phone.
        $request->validate([
            'name' => $user ? 'nullable|string' : 'required|string',
            'email' => $user ? 'nullable|email' : 'required|email',
            'phone' => $user ? 'nullable|string' : 'required|string',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'delivery_phone' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
        ]);

        $itemsInput = $this->normalizeItems($request->input('items', []), false);

        $requestId = 'PKD' . mt_rand(10000000, 99999999);
        $userId = $user?->id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $pickupDelivery = PickupDelivery::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $user?->name ?? $request->name,
            'email' => $user?->email ?? $request->email,
            'phone' => $user?->phone ?? $request->phone ?? '',
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'delivery_phone' => $request->delivery_phone,
            'status' => 'pending',
        ]);

        // Customers can never set pricing: item costs are stored as null here.
        foreach ($itemsInput as $itemData) {
            PickupDeliveryItem::create([
                'pickup_delivery_id' => $pickupDelivery->id,
                'description' => $itemData['description'],
                'cost' => null,
            ]);
        }

        $pickupDelivery->calculateAndUpdateTotals();
        $pickupDelivery->load(['items', 'user']);

        try {
            Mail::to($pickupDelivery->email)->send(new PickupDeliveryCreatedMail($pickupDelivery));
        } catch (\Throwable $e) {
            Log::error("Failed to send PickupDeliveryCreatedMail to {$pickupDelivery->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Pickup and delivery request submitted successfully',
            'pickup_delivery' => $pickupDelivery
        ], 201);
    }

    // Get user's pickup & delivery requests (Requires Auth)
    public function index(Request $request)
    {
        $user = $request->user();

        // Auto-link any previous unlinked requests that match this user's email
        PickupDelivery::whereNull('user_id')
            ->where('email', $user->email)
            ->update(['user_id' => $user->id]);

        $requests = PickupDelivery::with('items')->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('email', $user->email);
        })->latest()->get();

        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = PickupDelivery::with(['items', 'user']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        $pickupDelivery = $query->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        return response()->json($pickupDelivery);
    }

    // Admin: Store pickup & delivery on behalf of a user
    // Admin: Store pickup & delivery on behalf of a user (multi-item, pricing allowed)
    public function adminStore(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => $request->user_id ? 'nullable|string' : 'required|string',
            'email' => $request->user_id ? 'nullable|email' : 'required|email',
            'phone' => $request->user_id ? 'nullable|string' : 'required|string',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'delivery_phone' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        $targetUser = $request->user_id ? \App\Models\User::find($request->user_id) : null;
        $userId = $targetUser?->id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $itemsInput = $this->normalizeItems($request->input('items', []), true);

        // Backwards compatibility: a legacy header-level cost becomes one item row.
        if (empty($itemsInput) && $request->filled('cost')) {
            $itemsInput = [[
                'description' => 'Pickup & delivery service',
                'cost' => is_numeric($request->cost) ? (float) $request->cost : null,
            ]];
        }

        $requestId = 'PKD' . mt_rand(10000000, 99999999);

        $pickupDelivery = PickupDelivery::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $targetUser?->name ?? $request->name,
            'email' => $targetUser?->email ?? $request->email,
            'phone' => $targetUser?->phone ?? $request->phone ?? '',
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'delivery_phone' => $request->delivery_phone,
            'cost' => $request->cost,
            'status' => 'pending',
        ]);

        foreach ($itemsInput as $itemData) {
            PickupDeliveryItem::create([
                'pickup_delivery_id' => $pickupDelivery->id,
                'description' => $itemData['description'],
                'cost' => $itemData['cost'],
            ]);
        }

        $pickupDelivery->calculateAndUpdateTotals();
        $pickupDelivery->load(['items', 'user']);

        try {
            Mail::to($pickupDelivery->email)->send(new PickupDeliveryCreatedMail($pickupDelivery));
        } catch (\Throwable $e) {
            Log::error("Failed to send PickupDeliveryCreatedMail to {$pickupDelivery->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Pickup & delivery request created successfully by admin',
            'pickup_delivery' => $pickupDelivery
        ], 201);
    }

    // Admin: List all requests
    public function adminIndex()
    {
        $requests = PickupDelivery::with(['items', 'user'])->latest()->get();
        return response()->json($requests);
    }

    // Admin: Full update (status, addresses, cost, and the full item list)
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'delivery_phone' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        $pickupDelivery = PickupDelivery::with(['items', 'user'])->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        foreach (['status', 'pickup_address', 'delivery_address', 'delivery_phone'] as $field) {
            if ($request->has($field)) {
                $pickupDelivery->$field = $request->$field;
            }
        }

        if ($request->has('items') && is_array($request->items)) {
            $this->syncItems($pickupDelivery, $request->items);
            // Drop the stale relation cache instead of refresh()ing: refresh()
            // reloads from DB and would discard the header edits made above.
            $pickupDelivery->unsetRelation('items');
        } elseif ($request->has('cost')) {
            $pickupDelivery->cost = $request->cost;
            $first = $pickupDelivery->items()->first();
            if ($first) {
                $first->update(['cost' => $request->cost]);
                $pickupDelivery->unsetRelation('items');
            }
        }

        $pickupDelivery->save();
        $pickupDelivery->calculateAndUpdateTotals();
        $pickupDelivery->load(['items', 'user']);

        $recipientEmail = $pickupDelivery->email ?? ($pickupDelivery->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new PickupDeliveryStatusUpdatedMail($pickupDelivery));
            } catch (\Throwable $e) {
                Log::error("Failed to send PickupDeliveryStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Pickup & delivery request updated successfully',
            'pickup_delivery' => $pickupDelivery
        ]);
    }

    // Admin: Update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'cost' => 'nullable|numeric'
        ]);

        $pickupDelivery = PickupDelivery::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $pickupDelivery->status = $request->status;
        if ($request->has('cost')) {
            $pickupDelivery->cost = $request->cost;
            $first = $pickupDelivery->items()->first();
            if ($first) {
                $first->update(['cost' => $request->cost]);
            }
        }
        $pickupDelivery->save();
        $pickupDelivery->calculateAndUpdateTotals();

        $recipientEmail = $pickupDelivery->email ?? ($pickupDelivery->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new PickupDeliveryStatusUpdatedMail($pickupDelivery));
            } catch (\Throwable $e) {
                Log::error("Failed to send PickupDeliveryStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Pickup & delivery request status updated',
            'pickup_delivery' => $pickupDelivery
        ]);
    }

    // Download Pickup & Delivery Invoice
    public function downloadInvoice(Request $request, $id)
    {
        $user = $request->user();
        $pickupDelivery = $this->resolveVisibleRecord($id, $user);

        if (!$pickupDelivery->invoice_generated && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Invoice has not been generated by admin yet.'], 403);
        }

        return $this->renderReceiptPdf($pickupDelivery, 'invoice');
    }

    // Download Pickup & Delivery Receipt (always available to the owner/admin)
    public function downloadReceipt(Request $request, $id)
    {
        $user = $request->user();
        $pickupDelivery = $this->resolveVisibleRecord($id, $user);

        return $this->renderReceiptPdf($pickupDelivery, 'receipt');
    }

    // Scope the record to the current user unless they are an admin.
    protected function resolveVisibleRecord($id, $user = null)
    {
        $query = PickupDelivery::with(['items', 'user']);

        if ($user && $user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        return $query->where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', (string) $id);
        })->firstOrFail();
    }

    // Build the shared receipt/invoice PDF from the restyled template blade.
    protected function renderReceiptPdf(PickupDelivery $pickupDelivery, string $kind = 'invoice')
    {
        $items = [];
        foreach ($pickupDelivery->items as $item) {
            if (!is_numeric($item->cost)) {
                continue;
            }
            $items[] = [
                'name' => $item->description,
                'amount' => (float) $item->cost,
            ];
        }

        $total = $pickupDelivery->pickupTotal();

        // Legacy records without priced item rows fall back to a single line.
        if (empty($items)) {
            if ($total <= 0) {
                $total = 25000; // historic default when nothing has been priced yet
            }
            $items[] = [
                'name' => 'Pickup & Delivery Logistics Package',
                'amount' => $total,
                'subtext' => 'From: ' . ($pickupDelivery->pickup_address ?? 'Origin') . ' -> To: ' . ($pickupDelivery->delivery_address ?? 'Destination'),
            ];
        } elseif ($total <= 0) {
            $total = array_sum(array_column($items, 'amount'));
        }

        $invoiceNumber = 'INV-' . strtoupper(substr(md5($pickupDelivery->request_id ?? ''), 0, 6));

        $pdf = Pdf::loadView('invoices.pickup-delivery-invoice', [
            'kind' => $kind,
            'pickup_delivery' => $pickupDelivery,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $pickupDelivery->name ?? ($pickupDelivery->user->name ?? 'Customer'),
            'invoice_date' => $pickupDelivery->created_at ? $pickupDelivery->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $pickupDelivery->created_at ? $pickupDelivery->created_at->format('d M Y') : date('d M Y'),
            'items' => $items,
            'total_amount' => $total,
            'bank_account_number' => \App\Models\Setting::get('bank_account_number', '0900779403'),
            'bank_account_name' => \App\Models\Setting::get('bank_account_name', 'Leezoe integrated'),
            'bank_name' => \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank.'),
        ]);

        $fileLabel = $kind === 'receipt' ? 'Receipt' : 'Invoice';

        return $pdf->download('Pickup-Delivery-' . $fileLabel . '-' . $invoiceNumber . '.pdf');
    }

    // Admin: Generate Invoice
    public function generateInvoice(Request $request, $id)
    {
        $pickupDelivery = PickupDelivery::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $pickupDelivery->invoice_generated = true;
        $pickupDelivery->save();

        $recipientEmail = $pickupDelivery->email ?? ($pickupDelivery->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new \App\Mail\InvoiceGeneratedMail($pickupDelivery, 'Pickup & Delivery'));
            } catch (\Throwable $e) {
                Log::error("Failed to send InvoiceGeneratedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Pickup & delivery invoice generated successfully and notification email sent to customer.',
            'pickup_delivery' => $pickupDelivery
        ]);
    }

    /**
     * Clean the incoming item rows (description + admin-only cost).
     * Customers never pass pricing, so $allowCost stays false on store().
     */
    protected function normalizeItems($raw, bool $allowCost): array
    {
        $items = [];

        foreach ((array) $raw as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $items[] = [
                'description' => $description,
                'cost' => $allowCost && isset($row['cost']) && is_numeric($row['cost'])
                    ? (float) $row['cost']
                    : null,
            ];
        }

        return $items;
    }

    // Replace the full item list for a request (admin edits).
    protected function syncItems(PickupDelivery $pickupDelivery, array $raw): void
    {
        $itemsInput = $this->normalizeItems($raw, true);

        $pickupDelivery->items()->delete();

        foreach ($itemsInput as $itemData) {
            PickupDeliveryItem::create([
                'pickup_delivery_id' => $pickupDelivery->id,
                'description' => $itemData['description'],
                'cost' => $itemData['cost'],
            ]);
        }
    }
}

