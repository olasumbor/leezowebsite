@extends('invoices.layout-invoice')

@section('accent_vars')
:root { --accent:#2563eb; --accent-dark:#1e3a8a; --accent-light:#eff6ff; --accent-soft:#dbeafe; }
@endsection

@section('invoiceType')
Shipment Invoice
@endsection

@section('bannerTitle')
SHIPMENT INVOICE
@endsection

@section('details')
<div class="details-grid">
    <div class="details-item"><div class="dl">Tracking ID</div><div class="dv">{{ $shipment->tracking_id ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Service</div><div class="dv">{{ $shipment->service ?? 'Air Freight' }}</div></div>
    <div class="details-item"><div class="dl">Origin</div><div class="dv">{{ $shipment->origin ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Destination</div><div class="dv">{{ $shipment->destination ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Weight</div><div class="dv">{{ $shipment->weight ? $shipment->weight . ' kg' : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Packages</div><div class="dv">{{ $shipment->packages ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Recipient</div><div class="dv">{{ $shipment->recipient_name ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Expected Delivery</div><div class="dv">{{ $shipment->expected_delivery_date ? \Carbon\Carbon::parse($shipment->expected_delivery_date)->format('d M Y') : 'N/A' }}</div></div>
</div>
@endsection