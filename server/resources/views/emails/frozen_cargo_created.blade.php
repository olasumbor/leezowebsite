@extends('emails.layout', ['title' => 'Frozen Cargo Request Confirmed'])

@section('content')
    <h2>Frozen Cargo Service Request Received</h2>
    <p>Dear {{ $requestItem->name }},</p>
    <p>Thank you for submitting a Cold-Chain / Frozen Cargo request with <strong>Leezofood NG.Export</strong>.</p>
    
    <div class="info-box">
        <p><strong>Request ID:</strong> {{ $requestItem->request_id }}</p>
        <p><strong>Temperature Requirement:</strong> {{ $requestItem->temperature_requirement }}</p>
        <p><strong>Route:</strong> {{ $requestItem->origin }} &rarr; {{ $requestItem->destination }}</p>
        <p><strong>Items ({{ $requestItem->items ? $requestItem->items->count() : 0 }}):</strong></p>
        <ul style="margin: 6px 0 0 18px; padding: 0;">
            @forelse($requestItem->items as $item)
                <li>
                    {{ $item->description }}
                    @if($item->quantity) &mdash; Qty: {{ $item->quantity }} @endif
                    @if($item->weight) &mdash; {{ $item->weight }} kg @endif
                </li>
            @empty
                <li>{{ $requestItem->cargo_description ?: 'Frozen cargo item' }}</li>
            @endforelse
        </ul>
        @if($requestItem->notes) <p><strong>Special Handling Notes:</strong> {{ $requestItem->notes }}</p> @endif
        <p><strong>Status:</strong> <span class="status-badge pending">{{ strtoupper($requestItem->status ?? 'PENDING') }}</span></p>
    </div>

    <p>Our cold-chain logistics team is preparing your transport schedule and will contact you shortly.</p>
@endsection
