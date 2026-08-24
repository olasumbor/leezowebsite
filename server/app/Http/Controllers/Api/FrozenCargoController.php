<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrozenCargo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\FrozenCargoCreatedMail;

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
        $userId = $request->user() ? $request->user()->id : null;

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
        $requests = FrozenCargo::where('user_id', $request->user()->id)->latest()->get();
        return response()->json($requests);
    }

    // Get single request details
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = FrozenCargo::query();

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $frozenCargo = $query->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('request_id', $id);
        })->firstOrFail();

        return response()->json($frozenCargo);
    }

    // Admin: List all requests
    public function adminIndex()
    {
        $requests = FrozenCargo::with('user')->latest()->get();
        return response()->json($requests);
    }

    // Admin: Update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $frozenCargo = FrozenCargo::where('id', $id)->orWhere('request_id', $id)->firstOrFail();
        $frozenCargo->status = $request->status;
        $frozenCargo->save();

        return response()->json([
            'message' => 'Frozen cargo request status updated',
            'frozen_cargo' => $frozenCargo
        ]);
    }
}
