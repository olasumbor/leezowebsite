@extends('emails.layout', ['title' => 'We Received Your Message'])

@section('content')
    <h2>Thank you for contacting us!</h2>
    <p>Dear {{ $contactMessage->name }},</p>
    <p>We have received your message regarding <strong>{{ $contactMessage->subject ?? 'General Enquiry' }}</strong>. Our customer support team will review your message and reply to you as soon as possible.</p>
    
    <div class="info-box">
        <p><strong>Message Reference:</strong> MSG-{{ $contactMessage->id }}</p>
        <p><strong>Subject:</strong> {{ $contactMessage->subject ?? 'Enquiry' }}</p>
        <p><strong>Your Message:</strong> {{ $contactMessage->message }}</p>
    </div>

    <p>If your matter is urgent, feel free to call us at <strong>+234 809 499 7264</strong> or reach us on WhatsApp.</p>
@endsection
