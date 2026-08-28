@extends('emails.layout', ['title' => 'Frozen Cargo Request Updated'])

@section('content')
    <h2>Frozen Cargo Status Update</h2>
    <p>Dear {{ $frozenCargo->name ?? ($frozenCargo->user->name ?? 'Valued Customer') }},</p>
    <p>There is an update on your Cold-Chain / Frozen Cargo request <strong>{{ $frozenCargo->request_id ?? ('RQST-' . $frozenCargo->id) }}</strong>.</p>
    
    <div class="info-box">
        <p><strong>Request ID:</strong> {{ $frozenCargo->request_id ?? ('RQST-' . $frozenCargo->id) }}</p>
        <p><strong>Current Status:</strong> <span class="status-badge {{ strtolower($frozenCargo->status) === 'completed' ? 'completed' : 'pending' }}">{{ strtoupper($frozenCargo->status ?? 'PENDING') }}</span></p>
        <p><strong>Temperature Requirement:</strong> {{ $frozenCargo->temperature_requirement ?? 'Frozen' }}</p>
        <p><strong>Route:</strong> {{ $frozenCargo->origin }} &rarr; {{ $frozenCargo->destination }}</p>
        @if($frozenCargo->cargo_description) <p><strong>Cargo Description:</strong> {{ $frozenCargo->cargo_description }}</p> @endif
        @if($frozenCargo->notes) <p><strong>Notes:</strong> {{ $frozenCargo->notes }}</p> @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/frozen-cargo-details.html?id={{ $frozenCargo->request_id ?? $frozenCargo->id }}" class="action-btn">View Request Details</a>
    </p>
@endsection
