<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PickupDeliveryCreatedMail;

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
            'pickup_date' => 'nullable|date',
            'delivery_address' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $requestId = 'PKD' . mt_rand(10000000, 99999999);
        $userId = $request->user() ? $request->user()->id : null;

        $pickupDelivery = PickupDelivery::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'pickup_address' => $request->pickup_address,
            'pickup_date' => $request->pickup_date,
            'delivery_address' => $request->delivery_address,
            'item_description' => $request->item_description,
            'weight' => $request->weight,
            'notes' => $request->notes,
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
        $requests = PickupDelivery::where('user_id', $request->user()->id)->latest()->get();
        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = PickupDelivery::query();

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
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

        $pickupDelivery = PickupDelivery::where('id', $id)->orWhere('request_id', $id)->firstOrFail();
        $pickupDelivery->status = $request->status;
        $pickupDelivery->save();

        return response()->json([
            'message' => 'Pickup & delivery request status updated',
            'pickup_delivery' => $pickupDelivery
        ]);
    }
}
