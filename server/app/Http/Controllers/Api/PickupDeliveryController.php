<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PickupDeliveryCreatedMail;
use App\Mail\PickupDeliveryStatusUpdatedMail;

class PickupDeliveryController extends Controller
{
    // Create new pickup & delivery request
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'delivery_phone' => 'nullable|string',
        ]);

        $requestId = 'PKD' . mt_rand(10000000, 99999999);
        $user = $request->user('sanctum') ?? $request->user();
        $userId = $user ? $user->id : null;

        if (!$userId && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            }
        }

        $pickupDelivery = PickupDelivery::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'delivery_phone' => $request->delivery_phone,
            'status' => 'pending',
        ]);

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

        $requests = PickupDelivery::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('email', $user->email);
        })->latest()->get();

        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = PickupDelivery::query();

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

    // Admin: List all requests
    public function adminIndex()
    {
        $requests = PickupDelivery::with('user')->latest()->get();
        return response()->json($requests);
    }

    // Admin: Update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $pickupDelivery = PickupDelivery::with('user')->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        $pickupDelivery->status = $request->status;
        $pickupDelivery->save();

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
        $query = PickupDelivery::with('user');

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            });
        }

        $pickupDelivery = $query->where(function ($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', (string) $id);
        })->firstOrFail();

        if (!$pickupDelivery->invoice_generated && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Invoice has not been generated by admin yet.'], 403);
        }

        $cost = 25000;

        $items = [
            [
                'name' => 'Pickup & Delivery Logistics Package',
                'amount' => $cost,
                'subtext' => 'From: ' . ($pickupDelivery->pickup_address ?? 'Origin') . ' -> To: ' . ($pickupDelivery->delivery_address ?? 'Destination')
            ]
        ];

        return response()->view('invoices.invoice', [
            'invoice_number' => 'INV-' . strtoupper(substr(md5($pickupDelivery->request_id ?? $id), 0, 6)),
            'customer_name' => $pickupDelivery->name ?? ($pickupDelivery->user->name ?? 'Customer'),
            'invoice_date' => $pickupDelivery->created_at ? $pickupDelivery->created_at->format('d M Y') : date('d M Y'),
            'due_date' => $pickupDelivery->created_at ? $pickupDelivery->created_at->format('d M Y') : date('d M Y'),
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
}

