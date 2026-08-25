@extends('emails.layout', ['title' => 'New Shipment Created'])

@section('content')
    <h2>Your Shipment is Ready for Tracking!</h2>
    <p>Dear {{ $shipment->user->name ?? 'Valued Customer' }},</p>
    <p>A new shipment has been created for your order. You can use your official tracking number below to monitor your cargo in real-time.</p>
    
    <div class="info-box">
        <p><strong>Tracking ID:</strong> {{ $shipment->tracking_id }}</p>
        <p><strong>Service Type:</strong> {{ $shipment->service ?? 'Logistics Freight' }}</p>
        <p><strong>Origin &rarr; Destination:</strong> {{ $shipment->origin }} &rarr; {{ $shipment->destination }}</p>
        <p><strong>Status:</strong> <span class="status-badge pending">{{ strtoupper($shipment->status ?? 'PENDING') }}</span></p>
        @if($shipment->expected_delivery_date) <p><strong>Expected Delivery:</strong> {{ $shipment->expected_delivery_date }}</p> @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/shipment-details.html?id={{ $shipment->tracking_id }}" class="action-btn">Track Shipment Now</a>
    </p>
@endsection
