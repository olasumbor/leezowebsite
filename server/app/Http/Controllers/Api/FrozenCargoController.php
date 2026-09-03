<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrozenCargo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\FrozenCargoCreatedMail;
use App\Mail\FrozenCargoStatusUpdatedMail;

class FrozenCargoController extends Controller
{
    // Create new frozen cargo request
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'cargo_description' => 'required|string',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'departure_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $requestId = 'RQST' . mt_rand(1000000, 9999999);
        $user = $request->user('sanctum') ?? $request->user();
        $userId = $user ? $user->id : null;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $frozenCargo = FrozenCargo::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cargo_description' => $request->cargo_description,
            'temperature_requirement' => $request->temperature_requirement,
            'weight' => $request->weight,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

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

        $requests = FrozenCargo::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('email', $user->email);
        })->latest()->get();

        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = FrozenCargo::query();

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

    // Admin: Store frozen cargo on behalf of a user
    public function adminStore(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'cargo_description' => 'required|string',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'departure_date' => 'nullable|date',
            'cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $requestId = 'RQST' . mt_rand(1000000, 9999999);
        $userId = $request->user_id;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $frozenCargo = FrozenCargo::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cargo_description' => $request->cargo_description,
            'temperature_requirement' => $request->temperature_requirement,
            'weight' => $request->weight,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'cost' => $request->cost,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

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
        $requests = FrozenCargo::with('user')->latest()->get();
        return response()->json($requests);
    }

    // Admin: Full update (status, cost, etc.)
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'temperature_requirement' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'origin' => 'nullable|string',
            'destination' => 'nullable|string',
            'departure_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $frozenCargo = FrozenCargo::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $frozenCargo->update($request->only([
            'status',
            'cost',
            'temperature_requirement',
            'weight',
            'origin',
            'destination',
            'departure_date',
            'notes',
        ]));

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

        $frozenCargo = FrozenCargo::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $frozenCargo->status = $request->status;
        if ($request->has('cost')) {
            $frozenCargo->cost = $request->cost;
        }
        $frozenCargo->save();

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

    // Download Frozen Cargo Receipt
    public function downloadReceipt(Request $request, $id)
    {
        return $this->downloadInvoice($request, $id);
    }

    // Download Frozen Cargo Invoice
    public function downloadInvoice(Request $request, $id)
    {
        $user = $request->user();
        $query = FrozenCargo::with('user');

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

        $cost = is_numeric($frozenCargo->cost) ? (float)$frozenCargo->cost : 75000;

        $items = [
            [
                'name' => 'Cold-Chain / Frozen Cargo: ' . ($frozenCargo->cargo_description ?? 'Temperature controlled shipment'),
                'amount' => $cost,
                'subtext' => 'Temp: ' . ($frozenCargo->temperature_requirement ?? 'Frozen') . ' | ' . ($frozenCargo->origin ?? 'Origin') . ' -> ' . ($frozenCargo->destination ?? 'Destination')
            ]
        ];

        return response()->view('invoices.invoice', [
            'invoice_number' => 'INV-' . strtoupper(substr(md5($frozenCargo->request_id ?? $id), 0, 6)),
            'customer_name' => $frozenCargo->name ?? ($frozenCargo->user->name ?? 'Customer'),
            'invoice_date' => $frozenCargo->created_at ? $frozenCargo->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $frozenCargo->departure_date ? \Carbon\Carbon::parse($frozenCargo->departure_date)->format('d M Y') : date('d M Y'),
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
        $frozenCargo = FrozenCargo::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        if (empty($frozenCargo->cost) || !is_numeric($frozenCargo->cost) || (float)$frozenCargo->cost <= 0) {
            return response()->json([
                'message' => 'Cannot generate invoice: Frozen cargo cost (price) has not been set yet. Please edit details and set a price first.'
            ], 422);
        }

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
}

