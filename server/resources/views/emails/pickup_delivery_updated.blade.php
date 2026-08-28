@extends('emails.layout', ['title' => 'Pick & Delivery Request Updated'])

@section('content')
    <h2>Pick & Delivery Status Update</h2>
    <p>Dear {{ $pickupDelivery->name ?? ($pickupDelivery->user->name ?? 'Valued Customer') }},</p>
    <p>There is an update on your Pick & Delivery request <strong>{{ $pickupDelivery->request_id ?? ('PKD-' . $pickupDelivery->id) }}</strong>.</p>
    
    <div class="info-box">
        <p><strong>Request ID:</strong> {{ $pickupDelivery->request_id ?? ('PKD-' . $pickupDelivery->id) }}</p>
        <p><strong>Current Status:</strong> <span class="status-badge {{ strtolower($pickupDelivery->status) === 'completed' ? 'completed' : 'pending' }}">{{ strtoupper($pickupDelivery->status ?? 'PENDING') }}</span></p>
        <p><strong>Pickup Address:</strong> {{ $pickupDelivery->pickup_address }}</p>
        <p><strong>Delivery Address:</strong> {{ $pickupDelivery->delivery_address }}</p>
        @if($pickupDelivery->item_description) <p><strong>Item Description:</strong> {{ $pickupDelivery->item_description }}</p> @endif
        @if($pickupDelivery->notes) <p><strong>Notes:</strong> {{ $pickupDelivery->notes }}</p> @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/pickup-delivery-details.html?id={{ $pickupDelivery->request_id ?? $pickupDelivery->id }}" class="action-btn">View Request Details</a>
    </p>
@endsection
