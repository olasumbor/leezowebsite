@extends('invoices.layout-invoice')

@section('accent_vars')
:root { --accent:#0f766e; --accent-dark:#115e59; --accent-light:#f0fdfa; --accent-soft:#ccfbf1; }
@endsection

@section('invoiceType')
Pickup &amp; Delivery Invoice
@endsection

@section('bannerTitle')
PICKUP &amp; DELIVERY INVOICE
@endsection

@section('details')
<div class="details-grid">
    <div class="details-item"><div class="dl">Request ID</div><div class="dv">{{ $pickupDelivery->request_id ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Pickup Address</div><div class="dv">{{ $pickupDelivery->pickup_address ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Delivery Address</div><div class="dv">{{ $pickupDelivery->delivery_address ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Pickup Date</div><div class="dv">{{ $pickupDelivery->pickup_date ? \Carbon\Carbon::parse($pickupDelivery->pickup_date)->format('d M Y') : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Weight</div><div class="dv">{{ $pickupDelivery->weight ? $pickupDelivery->weight . ' kg' : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Item Description</div><div class="dv">{{ $pickupDelivery->item_description ?? 'N/A' }}</div></div>
</div>
@endsection