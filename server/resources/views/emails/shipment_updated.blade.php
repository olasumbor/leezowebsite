@extends('emails.layout', ['title' => 'Shipment Tracking Update'])

@section('content')
    <h2>Shipment Progress Update</h2>
    <p>Dear {{ $shipment->user->name ?? 'Valued Customer' }},</p>
    <p>We have an update regarding your shipment <strong>{{ $shipment->tracking_id }}</strong>.</p>
    
    <div class="info-box">
        <p><strong>Tracking ID:</strong> {{ $shipment->tracking_id }}</p>
        <p><strong>Status:</strong> <span class="status-badge {{ strtolower($shipment->status) === 'delivered' ? 'completed' : 'pending' }}">{{ strtoupper($shipment->status) }}</span></p>
        @if(isset($latestEvent) && $latestEvent)
            <p><strong>Latest Update:</strong> {{ $latestEvent->description }} ({{ $latestEvent->location }})</p>
        @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/shipment-details.html?id={{ $shipment->tracking_id }}" class="action-btn">View Shipment Timeline</a>
    </p>
@endsection
