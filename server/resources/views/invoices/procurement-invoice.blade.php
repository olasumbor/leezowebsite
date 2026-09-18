@extends('invoices.layout-invoice')

@section('accent_vars')
:root { --accent:#d97706; --accent-dark:#92400e; --accent-light:#fffbeb; --accent-soft:#fef3c7; }
@endsection

@section('invoiceType')
Procurement Invoice
@endsection

@section('bannerTitle')
PROCUREMENT INVOICE
@endsection

@section('details')
<div class="details-grid">
    <div class="details-item"><div class="dl">Procurement ID</div><div class="dv">{{ $procurement->procurement_id ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Category</div><div class="dv">{{ $procurement->category ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Quantity</div><div class="dv">{{ $procurement->quantity ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Supplier</div><div class="dv">{{ $procurement->supplier ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Location</div><div class="dv">{{ $procurement->location ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Recipient Location</div><div class="dv">{{ $procurement->recipient_location ?? 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Expected Date</div><div class="dv">{{ $procurement->expected_date ? \Carbon\Carbon::parse($procurement->expected_date)->format('d M Y') : 'N/A' }}</div></div>
    <div class="details-item"><div class="dl">Details</div><div class="dv">{{ $procurement->details ?? 'N/A' }}</div></div>
</div>
@endsection