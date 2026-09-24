<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrozenCargo;
use App\Models\FrozenCargoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\FrozenCargoCreatedMail;
use App\Mail\FrozenCargoStatusUpdatedMail;
use App\Support\Pdf;

class FrozenCargoController extends Controller
{
    // Create new frozen cargo request (multi-item; rate/cost are admin-only)
    public function store(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        // The dashboard form sends no identity fields: an authenticated request is
        // attached to (and filled from) the current user. Guests using the public
        // API directly must still provide name/email/phone.
        $request->validate([
            'name' => $user ? 'nullable|string' : 'required|string',
            'email' => $user ? 'nullable|email' : 'required|email',
            'phone' => $user ? 'nullable|string' : 'required|string',
            'cargo_description' => 'nullable|string',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'departure_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
        ]);

        $itemsInput = $this->extractItems($request);

        // Backwards compatibility: a legacy single description becomes one item.
        if (empty($itemsInput) && $request->filled('cargo_description')) {
            $itemsInput = [[
                'description' => trim((string) $request->cargo_description),
                'quantity' => 1,
                'weight' => is_numeric($request->weight) ? (float) $request->weight : null,
            ]];
        }

        if (empty($itemsInput)) {
            return response()->json([
                'message' => 'Please add at least one frozen cargo item.',
                'errors' => ['items' => ['At least one item is required.']],
            ], 422);
        }

        $requestId = 'RQST' . mt_rand(1000000, 9999999);
        $userId = $user?->id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $firstDescription = $itemsInput[0]['description'] ?? null;
        $totalWeight = $this->sumWeights($itemsInput);

        $frozenCargo = FrozenCargo::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $user?->name ?? $request->name,
            'email' => $user?->email ?? $request->email,
            'phone' => $user?->phone ?? $request->phone ?? '',
            'cargo_description' => $firstDescription,
            'temperature_requirement' => $request->temperature_requirement,
            'weight' => $totalWeight ?? $request->weight,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        // Customers can never set pricing: rate/cost are always stored as null here.
        foreach ($itemsInput as $itemData) {
            FrozenCargoItem::create([
                'frozen_cargo_id' => $frozenCargo->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'] ?? 0,
                'weight' => $itemData['weight'] ?? null,
                'rate' => null,
                'cost' => null,
            ]);
        }

        $frozenCargo->calculateAndUpdateTotals();
        $frozenCargo->load(['items', 'user']);

        try {
            Mail::to($frozenCargo->email)->send(new FrozenCargoCreatedMail($frozenCargo));
        } catch (\Throwable $e) {
            Log::error("Failed to send FrozenCargoCreatedMail to {$frozenCargo->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Frozen cargo request submitted successfully',
            'frozen_cargo' => $frozenCargo
        ], 201);
    }

    // Get user's frozen cargo requests (Requires Auth)
    public function index(Request $request)
    {
        $user = $request->user();

        // Auto-link any previous unlinked requests that match this user's email
        FrozenCargo::whereNull('user_id')
            ->where('email', $user->email)
            ->update(['user_id' => $user->id]);

        $requests = FrozenCargo::with('items')->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('email', $user->email);
        })->latest()->get();

        // Pricing is readable by owners, but never editable client-side (see store()).

        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = FrozenCargo::with(['items', 'user']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        $frozenCargo = $query->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        return response()->json($frozenCargo);
    }

    // Admin: Store frozen cargo on behalf of a user (multi-item, pricing allowed)
    public function adminStore(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'cargo_description' => 'nullable|string',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'departure_date' => 'nullable|date',
            'cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        $requestId = 'RQST' . mt_rand(1000000, 9999999);
        $userId = $request->user_id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $itemsInput = $this->normalizeItems($request->input('items', []), true);
        if (empty($itemsInput) && $request->filled('cargo_description')) {
            $itemsInput = [[
                'description' => trim((string) $request->cargo_description),
                'quantity' => 1,
                'weight' => is_numeric($request->weight) ? (float) $request->weight : null,
                'rate' => null,
                'cost' => is_numeric($request->cost) ? (float) $request->cost : null,
            ]];
        }
        $firstDescription = $itemsInput[0]['description'] ?? $request->cargo_description;
        $totalWeight = !empty($itemsInput) ? $this->sumWeights($itemsInput) : null;

        $frozenCargo = FrozenCargo::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cargo_description' => $firstDescription,
            'temperature_requirement' => $request->temperature_requirement,
            'weight' => $totalWeight ?? $request->weight,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'cost' => $request->cost,
            'notes' => $request->notes,
            'status' => $request->status ?? 'pending',
        ]);

        foreach ($itemsInput as $itemData) {
            FrozenCargoItem::create([
                'frozen_cargo_id' => $frozenCargo->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'] ?? 0,
                'weight' => $itemData['weight'] ?? null,
                'rate' => $itemData['rate'] ?? null,
                'cost' => $itemData['cost'] ?? null,
            ]);
        }
        $frozenCargo->calculateAndUpdateTotals();
        $frozenCargo->load(['items', 'user']);

        try {
            Mail::to($frozenCargo->email)->send(new FrozenCargoCreatedMail($frozenCargo));
        } catch (\Throwable $e) {
            Log::error("Failed to send FrozenCargoCreatedMail to {$frozenCargo->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Frozen cargo request created successfully by admin',
            'frozen_cargo' => $frozenCargo
        ], 201);
    }

    // Admin: List all requests
    public function adminIndex()
    {
        $requests = FrozenCargo::with(['items', 'user'])->latest()->get();
        return response()->json($requests);
    }

    // Admin: Full update (header fields + full item list with pricing)
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'cargo_description' => 'nullable|string',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'nullable|string',
            'destination' => 'nullable|string',
            'departure_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        $frozenCargo = FrozenCargo::with('items')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        foreach (['name','email','phone','temperature_requirement','origin',
            'destination','departure_date','notes','status'] as $field) {
            if ($request->has($field)) {
                $frozenCargo->$field = $request->$field;
            }
        }

        if ($request->has('items') && is_array($request->items)) {
            $this->syncItems($frozenCargo, $request->items, true);
            // Drop the stale relation cache instead of refresh()ing: refresh()
            // reloads from DB and would discard the header edits made above.
            $frozenCargo->unsetRelation('items');
            $this->syncHeaderFromItems($frozenCargo);
        } else {
            if ($request->has('cargo_description') && $request->cargo_description) {
                $frozenCargo->cargo_description = $request->cargo_description;
                $first = $frozenCargo->items()->first();
                if ($first) {
                    $first->update(['description' => $request->cargo_description]);
                } else {
                    FrozenCargoItem::create([
                        'frozen_cargo_id' => $frozenCargo->id,
                        'description' => $request->cargo_description,
                        'quantity' => 1,
                        'weight' => $frozenCargo->weight,
                    ]);
                }
            }
            if ($request->has('weight')) {
                $frozenCargo->weight = $request->weight;
            }
        }

        if ($request->has('cost') && !$request->has('items')) {
            $frozenCargo->cost = $request->cost;
        }

        $frozenCargo->save();
        $frozenCargo->calculateAndUpdateTotals();
        $frozenCargo->load(['items', 'user']);

        $recipientEmail = $frozenCargo->email ?? ($frozenCargo->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new FrozenCargoStatusUpdatedMail($frozenCargo));
            } catch (\Throwable $e) {
                Log::error("Failed to send FrozenCargoStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Frozen cargo request updated successfully',
            'frozen_cargo' => $frozenCargo
        ]);
    }

    // Admin: Update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'cost' => 'nullable|numeric'
        ]);

        $frozenCargo = FrozenCargo::with('items')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $frozenCargo->status = $request->status;
        if ($request->has('cost')) {
            $frozenCargo->cost = $request->cost;
            $first = $frozenCargo->items()->first();
            if ($first) {
                $first->update(['cost' => $request->cost]);
            }
        }
        $frozenCargo->save();
        $frozenCargo->calculateAndUpdateTotals();

        $recipientEmail = $frozenCargo->email ?? ($frozenCargo->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new FrozenCargoStatusUpdatedMail($frozenCargo));
            } catch (\Throwable $e) {
                Log::error("Failed to send FrozenCargoStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Frozen cargo request status updated',
            'frozen_cargo' => $frozenCargo
        ]);
    }

    // Download Frozen Cargo Invoice
    public function downloadInvoice(Request $request, $id)
    {
        $user = $request->user();
        $query = FrozenCargo::with(['items', 'user']);

        if ($user && $user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        $frozenCargo = $query->where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', (string) $id);
        })->firstOrFail();

        if (!$frozenCargo->invoice_generated && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Invoice has not been generated by admin yet.'], 403);
        }

        $total = $frozenCargo->frozenTotal();
        if ($total <= 0) {
            $total = 75000;
        }

        $items = [];
        if ($frozenCargo->items && $frozenCargo->items->count() > 0) {
            foreach ($frozenCargo->items as $item) {
                $amount = null;
                if ($item->cost !== null && is_numeric($item->cost)) {
                    $amount = (float) $item->cost;
                } elseif ($item->quantity !== null && $item->rate !== null
                    && is_numeric($item->quantity) && is_numeric($item->rate)) {
                    $amount = (float) $item->quantity * (float) $item->rate;
                }
                $parts = [];
                if ($item->quantity !== null) {
                    $parts[] = 'Qty: ' . $item->quantity;
                }
                if ($item->weight !== null) {
                    $parts[] = $item->weight . ' kg';
                }
                if ($item->rate !== null && is_numeric($item->rate)) {
                    $parts[] = 'Rate: NGN' . number_format((float) $item->rate, 2);
                }
                $parts[] = 'Temp: ' . ($frozenCargo->temperature_requirement ?? 'Frozen');
                $items[] = [
                    'name' => $item->description,
                    'amount' => $amount ?? 0,
                    'subtext' => ($frozenCargo->origin ?? 'Origin') . ' -> ' . ($frozenCargo->destination ?? 'Destination') . ' | ' . implode(' | ', $parts),
                ];
            }
        } else {
            $items = [
                [
                    'name' => 'Cold-Chain / Frozen Cargo: ' . ($frozenCargo->cargo_description ?? 'Temperature controlled shipment'),
                    'amount' => $total,
                    'subtext' => 'Temp: ' . ($frozenCargo->temperature_requirement ?? 'Frozen') . ' | ' . ($frozenCargo->origin ?? 'Origin') . ' -> ' . ($frozenCargo->destination ?? 'Destination')
                ]
            ];
        }

        $invoiceNumber = $frozenCargo->request_id ?? (string) $id;

        $pdf = Pdf::loadView('invoices.frozen-cargo-invoice', [
            'frozen_cargo' => $frozenCargo,
            'frozenCargo' => $frozenCargo,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $frozenCargo->name ?? ($frozenCargo->user->name ?? 'Customer'),
            'invoice_date' => $frozenCargo->created_at ? $frozenCargo->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $frozenCargo->departure_date ? \Carbon\Carbon::parse($frozenCargo->departure_date)->format('d M Y') : date('d M Y'),
            'items' => $items,
            'total_amount' => $total,
            'bank_account_number' => \App\Models\Setting::get('bank_account_number', '0900779403'),
            'bank_account_name' => \App\Models\Setting::get('bank_account_name', 'Leezoe integrated'),
            'bank_name' => \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank.'),
        ]);

        return $pdf->download('Frozen-Cargo-Invoice-' . $invoiceNumber . '.pdf');
    }

    // Admin: Generate Invoice
    public function generateInvoice(Request $request, $id)
    {
        $frozenCargo = FrozenCargo::with(['items', 'user'])->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $frozenCargo->invoice_generated = true;
        $frozenCargo->save();

        $recipientEmail = $frozenCargo->email ?? ($frozenCargo->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new \App\Mail\InvoiceGeneratedMail($frozenCargo, 'Frozen Cargo'));
            } catch (\Throwable $e) {
                Log::error("Failed to send InvoiceGeneratedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Frozen cargo invoice generated successfully and notification email sent to customer.',
            'frozen_cargo' => $frozenCargo
        ]);
    }

    // Admin: Replace items (with pricing)
    public function updateItems(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        $frozenCargo = FrozenCargo::where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $this->syncItems($frozenCargo, $request->items, true);

        $frozenCargo->refresh();
        $frozenCargo->unsetRelation('items');
        $this->syncHeaderFromItems($frozenCargo);
        $frozenCargo->calculateAndUpdateTotals();
        $frozenCargo->load(['items', 'user']);

        return response()->json([
            'message' => 'Frozen cargo items updated successfully',
            'frozen_cargo' => $frozenCargo,
        ]);
    }

    // ---- helpers ----

    /**
     * Pull item rows from the request. Pricing keys are stripped
     * unless $allowPricing (admin only).
     */
    private function extractItems(Request $request, bool $allowPricing = false): array
    {
        $raw = $request->input('items', $request->input('cargo_items', []));
        return $this->normalizeItems($raw, $allowPricing);
    }

    private function normalizeItems($rawItems, bool $allowPricing = false): array
    {
        $items = [];
        if (!is_array($rawItems)) {
            return $items;
        }
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $description = trim((string) ($raw['description'] ?? $raw['name'] ?? ''));
            if ($description === '') {
                continue;
            }
            $entry = [
                'description' => $description,
                'quantity' => isset($raw['quantity']) && $raw['quantity'] !== '' ? (int) $raw['quantity'] : 0,
                'weight' => isset($raw['weight']) && $raw['weight'] !== '' && is_numeric($raw['weight']) ? (float) $raw['weight'] : null,
            ];
            if ($allowPricing) {
                $entry['rate'] = isset($raw['rate']) && $raw['rate'] !== '' && is_numeric($raw['rate']) ? (float) $raw['rate'] : null;
                $entry['cost'] = isset($raw['cost']) && $raw['cost'] !== '' && is_numeric($raw['cost']) ? (float) $raw['cost'] : null;
            }
            $items[] = $entry;
        }
        return $items;
    }

    private function sumWeights(array $items): ?float
    {
        $total = 0;
        $found = false;
        foreach ($items as $item) {
            if (isset($item['weight']) && $item['weight'] !== null && is_numeric($item['weight'])) {
                $total += (float) $item['weight'];
                $found = true;
            }
        }
        return $found ? $total : null;
    }

    /**
     * Mirror item rows onto the legacy header columns after a sync:
     * cargo_description = first item's description, weight = summed item weight.
     * Reads via a fresh query (not the cached relation) so callers can invoke it
     * right after syncItems() without refresh()ing away pending header edits.
     */
    private function syncHeaderFromItems(FrozenCargo $frozenCargo): void
    {
        $items = $frozenCargo->items()->get();
        if ($items->count() === 0) {
            return;
        }

        $frozenCargo->cargo_description = $items->first()->description;

        $summed = $this->sumWeights($items->map(fn ($i) => ['weight' => $i->weight])->all());
        if ($summed !== null) {
            $frozenCargo->weight = $summed;
        }
    }

    private function syncItems(FrozenCargo $frozenCargo, $rawItems, bool $allowPricing = false): void
    {
        $normalized = $this->normalizeItems($rawItems, $allowPricing);
        FrozenCargoItem::where('frozen_cargo_id', $frozenCargo->id)->delete();
        foreach ($normalized as $item) {
            $payload = [
                'frozen_cargo_id' => $frozenCargo->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 0,
                'weight' => $item['weight'] ?? null,
            ];
            if ($allowPricing) {
                $payload['rate'] = $item['rate'] ?? null;
                $payload['cost'] = $item['cost'] ?? null;
            } else {
                $payload['rate'] = null;
                $payload['cost'] = null;
            }
            FrozenCargoItem::create($payload);
        }
    }
}

