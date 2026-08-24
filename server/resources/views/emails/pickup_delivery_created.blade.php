@extends('emails.layout', ['title' => 'Pick & Delivery Request Confirmed'])

@section('content')
    <h2>Pick & Delivery Request Received</h2>
    <p>Dear {{ $requestItem->name }},</p>
    <p>We have successfully received your Pick & Delivery request.</p>
    
    <div class="info-box">
        <p><strong>Request ID:</strong> {{ $requestItem->request_id }}</p>
        <p><strong>Pickup Address:</strong> {{ $requestItem->pickup_address }}</p>
        <p><strong>Delivery Address:</strong> {{ $requestItem->delivery_address }}</p>
        @if($requestItem->notes) <p><strong>Notes:</strong> {{ $requestItem->notes }}</p> @endif
        <p><strong>Status:</strong> <span class="status-badge pending">{{ strtoupper($requestItem->status ?? 'PENDING') }}</span></p>
    </div>

    <p>Our dispatch team will get in touch with you shortly to schedule pickup.</p>
@endsection
