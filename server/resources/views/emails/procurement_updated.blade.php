@extends('emails.layout', ['title' => 'Procurement Request Updated'])

@section('content')
    <h2>Procurement Status Update</h2>
    <p>Dear {{ $procurement->name }},</p>
    <p>There is an update on your procurement request <strong>{{ $procurement->procurement_id ?? ('PR-' . $procurement->id) }}</strong>.</p>
    
    <div class="info-box">
        <p><strong>Procurement ID:</strong> {{ $procurement->procurement_id ?? ('PR-' . $procurement->id) }}</p>
        <p><strong>Current Status:</strong> <span class="status-badge {{ strtolower($procurement->status) === 'completed' ? 'completed' : 'pending' }}">{{ strtoupper($procurement->status) }}</span></p>
        @if($procurement->supplier) <p><strong>Assigned Supplier:</strong> {{ $procurement->supplier }}</p> @endif
        @if($procurement->cost) <p><strong>Total Cost:</strong> ₦{{ number_format($procurement->cost, 2) }}</p> @endif
        @if($procurement->expected_date) <p><strong>Expected Delivery:</strong> {{ $procurement->expected_date }}</p> @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/procurement-details.html?id={{ $procurement->procurement_id ?? $procurement->id }}" class="action-btn">View Procurement Details</a>
    </p>
@endsection
