@extends('emails.layout', ['title' => 'Quote Request Received'])

@section('content')
    <h2>Quote Request Received!</h2>
    <p>Dear {{ $quote->name }},</p>
    <p>Thank you for requesting a freight & logistics quote from <strong>Leezofood NG.Export</strong>. Our pricing team is evaluating your request parameters.</p>
    
    <div class="info-box">
        @if(!empty($trackingId))
        <p><strong>Tracking ID:</strong> <span style="font-size: 16px; color: #00a63e; font-weight: 700;">{{ $trackingId }}</span></p>
        @endif
        <p><strong>Quote Reference:</strong> Q-{{ $quote->id }}</p>
        <p><strong>Shipping Type:</strong> {{ ucfirst($quote->shipping_type ?? 'Standard') }}</p>
        <p><strong>Route:</strong> {{ $quote->origin_country }} &rarr; {{ $quote->destination_country }}</p>
        <p><strong>Weight / Dimensions:</strong> {{ $quote->weight }}kg ({{ $quote->length }}x{{ $quote->width }}{{ $quote->height ? 'x'.$quote->height : '' }}cm)</p>
        <p><strong>Status:</strong> <span class="status-badge pending">{{ strtoupper($quote->status ?? 'PENDING') }}</span></p>
    </div>

    <p>We will email you as soon as your custom shipping rate is calculated. You can monitor your shipment status anytime using your tracking ID above.</p>
@endsection
