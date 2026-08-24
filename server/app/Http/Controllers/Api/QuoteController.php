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
            'shippingHeight' => 'required|numeric',
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
            'height' => $request->shippingHeight,
            'width' => $request->shippingWidth,
            'length' => $request->shippingLength,
            'shipping_details' => $request->shippingDetails,
            'status' => 'pending',
        ]);

        $volumetricWeight = ($request->shippingLength * $request->shippingWidth * $request->shippingHeight) / 5000;
        $chargeableWeight = max($request->shippingWeight, $volumetricWeight);

        $defaultRate = Setting::where('key', 'default_shipping_rate')->first();
        if ($defaultRate && is_numeric($defaultRate->value)) {
            $quote->calculated_cost = round($chargeableWeight * (float)$defaultRate->value, 2);
            $quote->save();
        }

        try {
            Mail::to($quote->email)->send(new QuoteSubmittedMail($quote));
        } catch (\Throwable $e) {
            Log::error("Failed to send QuoteSubmittedMail to {$quote->email}: " . $e->getMessage());
        }

        $requestId = 'RQST' . mt_rand(1000000, 9999999);

        return response()->json([
            'message' => 'Quote submitted successfully. Admin will review and provide a rate.',
            'request_id' => $requestId,
            'chargeable_weight' => round($chargeableWeight, 2),
            'volumetric_weight' => round($volumetricWeight, 2),
            'actual_weight' => $request->shippingWeight,
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

        try {
            Mail::to($quote->email)->send(new QuoteRateUpdatedMail($quote));
        } catch (\Throwable $e) {
            Log::error("Failed to send QuoteRateUpdatedMail to {$quote->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Quote cost updated successfully',
            'quote' => $quote
        ]);
    }
}
