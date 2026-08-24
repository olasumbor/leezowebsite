@extends('emails.layout', ['title' => 'Welcome to Leezofood NG.Export'])

@section('content')
    <h2>Welcome aboard, {{ $user->name }}!</h2>
    <p>Thank you for registering with <strong>Leezofood NG.Export</strong>. We are thrilled to have you on board!</p>
    <p>With your account, you can manage your export procurements, track shipments in real-time, submit quote requests, and coordinate pickup & delivery services effortlessly.</p>
    
    <div class="info-box">
        <p><strong>Account Email:</strong> {{ $user->email }}</p>
        <p><strong>Account Role:</strong> {{ ucfirst($user->role ?? 'User') }}</p>
    </div>

    <p style="text-align: center;">
        <a href="http://localhost:5500/client/signin.html" class="action-btn">Log In to Your Dashboard</a>
    </p>
    
    <p>If you have any questions or need assistance, feel free to contact our support team at <a href="mailto:info@leezofood.ng">info@leezofood.ng</a>.</p>
@endsection
