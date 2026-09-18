@extends('invoices.layout-invoice')

@section('accent_vars')
:root { --accent:#0891b2; --accent-dark:#164e63; --accent-light:#ecfeff; --accent-soft:#cffafe; }
@endsection

@section('invoiceType')
Frozen Cargo Invoice
@endsection

@section('bannerTitle')
FROZEN CARGO INVOICE
@endsection

@section('details')
<div class="details-grid">
    <div class="details-item"><div class="dl">Request ID</div><div class="dv">{{ $frozenCargo->request_id ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Cargo Description</div><div class="dv">{{ $frozenCargo->cargo_description ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Temperature</div><div class="dv">{{ $frozenCargo->temperature_requirement ?? 'Frozen (-18°C)' }}</div></div>
    <div class="details-item"><div class="dl">Weight</div><div class="dv">{{ $frozenCargo->weight ? $frozenCargo->weight . ' kg' : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Origin</div><div class="dv">{{ $frozenCargo->origin ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Destination</div><div class="dv">{{ $frozenCargo->destination ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Departure Date</div><div class="dv">{{ $frozenCargo->departure_date ? \Carbon\Carbon::parse($frozenCargo->departure_date)->format('d M Y') : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Notes</div><div class="dv">{{ $frozenCargo->notes ?? 'N/A' }}</div></div>
</div>
@endsection