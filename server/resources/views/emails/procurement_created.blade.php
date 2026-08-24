@extends('emails.layout', ['title' => 'Procurement Request Received'])

@section('content')
    <h2>Procurement Request Confirmed!</h2>
    <p>Dear {{ $procurement->name }},</p>
    <p>We have successfully received your procurement request. Our procurement team is reviewing your requirements and will begin processing shortly.</p>
    
    <div class="info-box">
        <p><strong>Procurement ID:</strong> {{ $procurement->procurement_id ?? ('PR-' . $procurement->id) }}</p>
        <p><strong>Details:</strong> {{ $procurement->details }}</p>
        <p><strong>Status:</strong> <span class="status-badge pending">{{ strtoupper($procurement->status ?? 'PENDING') }}</span></p>
        <p><strong>Date Submitted:</strong> {{ $procurement->created_at ? $procurement->created_at->format('M d, Y') : date('M d, Y') }}</p>
    </div>

    <p style="text-align: center;">
        <a href="http://localhost:5500/client/procurement-history.html" class="action-btn">View Procurement History</a>
    </p>
@endsection
