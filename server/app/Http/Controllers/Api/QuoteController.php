<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Setting;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\QuoteSubmittedMail;
use App\Mail\QuoteRateUpdatedMail;

class QuoteController extends Controller
{
    // Public: Submit a quote
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'shippingType' => 'required|string',
            'originCountry' => 'required|string',
            'destinationCountry' => 'required|string',
            'shippingWeight' => 'required|numeric',
            'shippingHeight' => 'nullable|numeric',
            'shippingWidth' => 'required|numeric',
            'shippingLength' => 'required|numeric',
            'shippingDetails' => 'required|string',
        ]);

        $quote = Quote::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'shipping_type' => $request->shippingType,
            'origin_country' => $request->originCountry,
            'destination_country' => $request->destinationCountry,
            'weight' => $request->shippingWeight,
            'height' => $request->shippingHeight ?? null,
            'width' => $request->shippingWidth,
            'length' => $request->shippingLength,
            'shipping_details' => $request->shippingDetails,
            'status' => 'pending',
            'calculated_cost' => null,
        ]);

        $trackingId = 'LEEZO' . mt_rand(1000000, 9999999);

        // Find or associate user if authenticated or matching email
        $user = $request->user('sanctum') ?? $request->user() ?? \App\Models\User::where('email', $request->email)->first();
        
        $shipment = \App\Models\Shipment::create([
            'tracking_id' => $trackingId,
            'user_id' => $user ? $user->id : null,
            'origin' => $request->originCountry,
            'destination' => $request->destinationCountry,
            'service' => $request->shippingType,
            'weight' => $request->shippingWeight . ' kg',
            'packages' => 1,
            'recipient_name' => $request->name,
            'recipient_location' => $request->destinationCountry,
            'status' => 'pending',
        ]);

        $shipment->events()->create([
            'location' => $request->originCountry,
            'description' => 'Shipment created from request quote form. Tracking ID: ' . $trackingId,
        ]);

        try {
            Mail::to($quote->email)->send(new QuoteSubmittedMail($quote, $trackingId));
        } catch (\Throwable $e) {
            Log::error("Failed to send QuoteSubmittedMail to {$quote->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Quote submitted successfully. Admin will review and provide a rate.',
            'request_id' => $trackingId,
            'tracking_id' => $trackingId,
            'quote_id' => $quote->id
        ], 201);
    }

    // Admin: List all quotes
    public function index()
    {
        $quotes = Quote::orderBy('created_at', 'desc')->get();
        return response()->json($quotes);
    }

    // Admin: Set cost/rate for a quote
    public function setCost(Request $request, $id)
    {
        $request->validate([
            'calculated_cost' => 'required|numeric',
            'status' => 'nullable|string'
        ]);

        $quote = Quote::findOrFail($id);
        $quote->calculated_cost = $request->calculated_cost;
        if ($request->has('status')) {
            $quote->status = $request->status;
        }
        $quote->save();

        $matchingShipment = \App\Models\Shipment::where('recipient_name', $quote->name)
            ->where('origin', $quote->origin_country)
            ->where('destination', $quote->destination_country)
            ->latest()
            ->first();
        $trackingId = $matchingShipment ? $matchingShipment->tracking_id : null;

        try {
            Mail::to($quote->email)->send(new QuoteRateUpdatedMail($quote, $trackingId));
        } catch (\Throwable $e) {
            Log::error("Failed to send QuoteRateUpdatedMail to {$quote->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Quote cost updated successfully',
            'quote' => $quote
        ]);
    }
}
