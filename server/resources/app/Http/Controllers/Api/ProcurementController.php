<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Procurement;
use App\Models\ProcurementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ProcurementCreatedMail;
use App\Mail\ProcurementStatusUpdatedMail;
use App\Support\Pdf;

class ProcurementController extends Controller
{
    // Create new procurement with items (Requires Auth)
    // Customers only submit item descriptions / logistics details. Pricing
    // (rate, cost, shipment fee, transportation) is read-only for them and is
    // written by the team through the admin endpoints.
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.category' => 'nullable|string',
            'items.*.supplier' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'expected_delivery' => 'nullable|date',
        ]);

        $procurementId = 'PR' . mt_rand(10000000, 99999999);

        $procurementData = [
            'procurement_id' => $procurementId,
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'pending',
            // Request date is always set automatically to today; it is
            // never accepted from the client form.
            'request_date' => now()->toDateString(),
            'expected_delivery' => $request->expected_delivery,
        ];

        $procurement = Procurement::create($procurementData);

        // Create procurement items if provided
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                ProcurementItem::create([
                    'procurement_id' => $procurement->id,
                    'description' => $itemData['description'],
                    'category' => $itemData['category'] ?? null,
                    'supplier' => $itemData['supplier'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 0,
                    'weight' => $itemData['weight'] ?? null,
                    // Pricing is read-only for customers: any rate/cost/fee values
                    // sent by the client are ignored and set later by the team.
                    'rate' => null,
                    'cost' => null,
                    'shipment_fee' => 0,
                    'transportation' => 0,
                ]);
            }
            // Calculate and update totals
            $procurement->load('items');
            $procurement->calculateAndUpdateTotals();
        }

        $procurement->load('items');

        try {
            Mail::to($procurement->email)->send(new ProcurementCreatedMail($procurement));
        } catch (\Throwable $e) {
            Log::error("Failed to send ProcurementCreatedMail to {$procurement->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Procurement created successfully',
            'procurement' => $procurement
        ], 201);
    }

    // Admin: Create procurement on behalf of user with items
    public function adminStore(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.category' => 'nullable|string',
            'items.*.supplier' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
            'items.*.shipment_fee' => 'nullable|numeric|min:0',
            'items.*.transportation' => 'nullable|numeric|min:0',
            'expected_delivery' => 'nullable|date',
            'category' => 'nullable|string',
            'quantity' => 'nullable|string',
            'supplier' => 'nullable|string',
            'cost' => 'nullable|numeric',
        ]);

        $procurementId = 'PR' . mt_rand(10000000, 99999999);
        $userId = $request->user_id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $procurementData = [
            'procurement_id' => $procurementId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'category' => $request->category,
            'quantity' => $request->quantity,
            'supplier' => $request->supplier,
            'cost' => $request->cost,
            'status' => 'pending',
            // Request date is always set automatically to today; it is
            // never accepted from the client form.
            'request_date' => now()->toDateString(),
            'expected_delivery' => $request->expected_delivery,
        ];

        $procurement = Procurement::create($procurementData);

        // Create procurement items if provided
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                ProcurementItem::create([
                    'procurement_id' => $procurement->id,
                    'description' => $itemData['description'],
                    'category' => $itemData['category'] ?? null,
                    'supplier' => $itemData['supplier'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 0,
                    'weight' => $itemData['weight'] ?? null,
                    'rate' => $itemData['rate'] ?? null,
                    'cost' => $itemData['cost'] ?? null,
                    'shipment_fee' => $itemData['shipment_fee'] ?? 0,
                    'transportation' => $itemData['transportation'] ?? 0,
                ]);
            }
            // Calculate and update totals
            $procurement->load('items');
            $procurement->calculateAndUpdateTotals();
        }

        $procurement->load('items');

        try {
            Mail::to($procurement->email)->send(new ProcurementCreatedMail($procurement));
        } catch (\Throwable $e) {
            Log::error("Failed to send ProcurementCreatedMail to {$procurement->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Procurement created successfully by admin',
            'procurement' => $procurement
        ], 201);
    }

    // Get user's procurements (Requires Auth)
    public function index(Request $request)
    {
        $user = $request->user();

        $procurements = Procurement::with(['user', 'items'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals for each procurement
        $procurements->each(function ($procurement) {
            $procurement->load('items');
            $procurement->calculateAndUpdateTotals();
            $procurement->makeHidden(['items']);
        });

        return response()->json($procurements);
    }

    // Get single procurement details (Public/Auth)
    public function show(Request $request, $id)
    {
        $user = $request->user('sanctum') ?? $request->user();

        $query = Procurement::with(['user', 'items']);

        if ($user && $user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        if (is_numeric($id)) {
            $procurement = $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('procurement_id', (string) $id);
            })->firstOrFail();
        } else {
            $procurement = $query->where('procurement_id', $id)->firstOrFail();
        }

        // Recalculate totals
        $procurement->calculateAndUpdateTotals();

        return response()->json($procurement);
    }

    // Get procurement with items for editing
    public function showWithItems($id)
    {
        $procurement = $this->findProcurement($id);
        $procurement->load('items');

        return response()->json($procurement);
    }

    // Generate PDF invoice for procurement
    public function generateInvoice($id)
    {
        $procurement = $this->findProcurement($id);
        $procurement->load('items');
        $procurement->calculateAndUpdateTotals();

        // Mark invoice as generated
        $procurement->invoice_generated = true;
        $procurement->save();

        try {
            Mail::to($procurement->email)->send(new ProcurementStatusUpdatedMail($procurement));
        } catch (\Throwable $e) {
            Log::error("Failed to send invoice notification to {$procurement->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Procurement invoice generated successfully and notification email sent to customer.',
            'procurement' => $procurement
        ]);
    }

    // Download PDF invoice
    public function downloadInvoice(Request $request, $id)
    {
        $user = $request->user('sanctum') ?? $request->user();
        $procurement = $this->findProcurement($id);

        // Invoice is only downloadable once the admin has generated it
        if (!$procurement->invoice_generated && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Invoice has not been generated by admin yet.'], 403);
        }

        $procurement->load('items');
        $procurement->calculateAndUpdateTotals();

        $bankName = \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank');
        $bankAccountName = \App\Models\Setting::get('bank_account_name', 'Leezo integrated');
        $bankAccountNumber = \App\Models\Setting::get('bank_account_number', '0900779403');

        $pdf = Pdf::loadView('invoices.procurement-invoice', [
            'procurement' => $procurement,
            'bank_name' => $bankName,
            'bank_account_name' => $bankAccountName,
            'bank_account_number' => $bankAccountNumber,
        ]);
        $pdf->setPaper('a4', 'landscape');
        $fileName = "Procurement_{$procurement->procurement_id}_Invoice.pdf";

        return $pdf->download($fileName);
    }

    private function findProcurement($id)
    {
        $query = Procurement::with('user');
        if (is_numeric($id)) {
            return $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('procurement_id', (string) $id);
            })->firstOrFail();
        }
        return $query->where('procurement_id', $id)->firstOrFail();
    }

    // Admin: List all procurements
    public function adminIndex()
    {
        $procurements = Procurement::with(['user', 'items'])->orderBy('created_at', 'desc')->get();
        
        // Calculate totals for each procurement
        $procurements->each(function ($procurement) {
            $procurement->load('items');
            $procurement->calculateAndUpdateTotals();
        });

        return response()->json($procurements);
    }

    // Admin: Get single procurement with items
    public function adminShow($id)
    {
        $procurement = $this->findProcurement($id);
        $procurement->load('items');
        $procurement->calculateAndUpdateTotals();

        return response()->json($procurement);
    }

    // Admin: Update procurement status, dates, recipient, and items in one call.
    // Item-level pricing (rate/cost/shipment fee/transportation) is admin-written;
    // totals are always recalculated server-side from the final item set.
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|string',
            'category' => 'nullable|string',
            'quantity' => 'nullable|string',
            'supplier' => 'nullable|string',
            'location' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
            'recipient_location' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'expected_delivery' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'receipt_date' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string',
            'items.*.category' => 'nullable|string',
            'items.*.supplier' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
            'items.*.shipment_fee' => 'nullable|numeric|min:0',
            'items.*.transportation' => 'nullable|numeric|min:0',
        ]);

        $procurement = $this->findProcurement($id);
        $procurement->load('items');

        // Note: request_date is intentionally excluded here. It is set
        // automatically on creation and must not be editable.
        $updateData = $request->only([
            'status',
            'category',
            'quantity',
            'supplier',
            'location',
            'expected_date',
            'delivered_date',
            'recipient_location',
            'cost',
            'expected_delivery',
            'delivery_date',
            'receipt_date',
        ]);

        $procurement->update($updateData);

        // Sync items (add / edit / remove) when the admin submits them
        if ($request->has('items') && is_array($request->items)) {
            ProcurementItem::where('procurement_id', $procurement->id)->delete();

            foreach ($request->items as $itemData) {
                if (empty($itemData['description'])) {
                    continue;
                }
                ProcurementItem::create([
                    'procurement_id' => $procurement->id,
                    'description' => $itemData['description'],
                    'category' => $itemData['category'] ?? null,
                    'supplier' => $itemData['supplier'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 0,
                    'weight' => $itemData['weight'] ?? null,
                    'rate' => $itemData['rate'] ?? null,
                    'cost' => $itemData['cost'] ?? null,
                    'shipment_fee' => $itemData['shipment_fee'] ?? 0,
                    'transportation' => $itemData['transportation'] ?? 0,
                ]);
            }
        }

        // Recalculate totals
        $procurement->calculateAndUpdateTotals();
        $procurement->load('items');

        $recipientEmail = $procurement->email ?? ($procurement->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ProcurementStatusUpdatedMail($procurement));
            } catch (\Throwable $e) {
                Log::error("Failed to send ProcurementStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Procurement updated successfully',
            'procurement' => $procurement
        ]);
    }

    // Admin: Update procurement items
    public function updateItems(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.category' => 'nullable|string',
            'items.*.supplier' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.cost' => 'nullable|numeric|min:0',
            'items.*.shipment_fee' => 'nullable|numeric|min:0',
            'items.*.transportation' => 'nullable|numeric|min:0',
        ]);

        $procurement = $this->findProcurement($id);

        // Delete existing items and create new ones
        ProcurementItem::where('procurement_id', $procurement->id)->delete();

        foreach ($request->items as $itemData) {
            ProcurementItem::create([
                'procurement_id' => $procurement->id,
                'description' => $itemData['description'],
                'category' => $itemData['category'] ?? null,
                'supplier' => $itemData['supplier'] ?? null,
                'quantity' => $itemData['quantity'] ?? 0,
                'weight' => $itemData['weight'] ?? null,
                'rate' => $itemData['rate'] ?? null,
                'cost' => $itemData['cost'] ?? null,
                'shipment_fee' => $itemData['shipment_fee'] ?? 0,
                'transportation' => $itemData['transportation'] ?? 0,
            ]);
        }

        // Recalculate totals
        $procurement->load('items');
        $procurement->calculateAndUpdateTotals();
        $procurement->load('items');

        return response()->json([
            'message' => 'Procurement items updated successfully',
            'procurement' => $procurement
        ]);
    }

    // Admin: Update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $procurement = $this->findProcurement($id);
        $procurement->status = $request->status;
        $procurement->save();

        $recipientEmail = $procurement->email ?? ($procurement->user->email ?? null);
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ProcurementStatusUpdatedMail($procurement));
            } catch (\Throwable $e) {
                Log::error("Failed to send ProcurementStatusUpdatedMail to {$recipientEmail}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Procurement status updated',
            'procurement' => $procurement
        ]);
    }
}
