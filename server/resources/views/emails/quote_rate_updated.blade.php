@extends('emails.layout', ['title' => 'Your Freight Rate is Ready'])

@section('content')
    <h2>Your Freight Shipping Rate is Ready!</h2>
    <p>Dear {{ $quote->name }},</p>
    <p>Great news! Our pricing team has calculated the freight rate for your quote request <strong>Q-{{ $quote->id }}</strong>.</p>
    
    <div class="info-box" style="border-left-color: #00a63e;">
        @if(!empty($trackingId))
        <p><strong>Tracking ID:</strong> <span style="font-size: 16px; color: #00a63e; font-weight: 700;">{{ $trackingId }}</span></p>
        @endif
        <p><strong>Quote Reference:</strong> Q-{{ $quote->id }}</p>
        <p><strong>Route:</strong> {{ $quote->origin_country }} &rarr; {{ $quote->destination_country }}</p>
        <p><strong>Weight:</strong> {{ $quote->weight }}kg</p>
        <p><strong>Calculated Shipping Cost:</strong> <span style="font-size: 18px; color: #00a63e; font-weight: 700;">₦{{ number_format($quote->calculated_cost, 2) }}</span></p>
        <p><strong>Status:</strong> <span class="status-badge completed">{{ strtoupper($quote->status ?? 'QUOTED') }}</span></p>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/contact.html" class="action-btn">Accept Quote & Proceed</a>
    </p>
@endsection
