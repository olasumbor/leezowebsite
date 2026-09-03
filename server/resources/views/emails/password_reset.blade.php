@extends('emails.layout', ['title' => 'Reset Your Password - Leezofood NG.Export'])

@section('content')
    <h2>Password Reset Request</h2>
    <p>Hello {{ $user->name ?? 'Valued Customer' }},</p>
    <p>You are receiving this email because we received a password reset request for your <strong>Leezofood NG.Export</strong> account.</p>
    
    <div class="info-box">
        <p><strong>Account Email:</strong> {{ $user->email }}</p>
        <p>This password reset link will expire in <strong>{{ $count ?? 60 }} minutes</strong>.</p>
    </div>

    <p style="text-align: center;">
        <a href="{{ $url }}" class="action-btn">Reset Password</a>
    </p>
    
    <p style="margin-top: 25px; font-size: 13px; color: #4b5563;">
        If you did not request a password reset, no further action is required and your account remains secure.
    </p>
    
    <p style="margin-top: 15px; font-size: 12px; color: #6b7280; word-break: break-all;">
        If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
        <a href="{{ $url }}" style="color: #10b981;">{{ $url }}</a>
    </p>
@endsection
