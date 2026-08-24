@extends('emails.layout', ['title' => 'New Contact Message Received'])

@section('content')
    <h2>New Customer Enquiry Received</h2>
    <p>A new customer message has been submitted via the website contact form.</p>
    
    <div class="info-box">
        <p><strong>Message ID:</strong> MSG-{{ $contactMessage->id }}</p>
        <p><strong>Sender Name:</strong> {{ $contactMessage->name }}</p>
        <p><strong>Email:</strong> {{ $contactMessage->email }}</p>
        <p><strong>Phone:</strong> {{ $contactMessage->phone ?? 'N/A' }}</p>
        <p><strong>Subject:</strong> {{ $contactMessage->subject ?? 'Enquiry' }}</p>
        <p><strong>Message Body:</strong></p>
        <p style="background: #ffffff; padding: 10px; border-radius: 4px; color: #1e293b;">{{ $contactMessage->message }}</p>
    </div>

    <p style="text-align: center;">
        <a href="http://localhost:5500/client/admin-dashboard.html" class="action-btn">Open Admin Dashboard</a>
    </p>
@endsection
