@extends('emails.layout', ['title' => 'Official Invoice Available'])

@section('content')
    <h2>Your Official Invoice Has Been Generated!</h2>
    <p>Dear {{ $customerName }},</p>
    <p>Your official invoice for <strong>{{ $orderType }} #{{ $orderId }}</strong> has been generated and is now ready for review and download.</p>
    
    <div class="info-box">
        <p><strong>Order Type:</strong> {{ $orderType }}</p>
        <p><strong>Order ID / Reference:</strong> {{ $orderId }}</p>
        @if(isset($order->shipping_cost) || isset($order->cost))
        <p><strong>Total Amount:</strong> NGN {{ number_format($order->shipping_cost ?? $order->cost ?? 0, 2) }}</p>
        @endif
    </div>

    <p style="text-align: center; margin-top: 25px;">
        <a href="{{ $detailsUrl }}" class="action-btn">View Order & Download Invoice</a>
    </p>

    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 20px;">
        If you have any questions regarding your invoice or order, please contact our support team at <a href="mailto:leezointegratedserviceslimited@gmail.com">leezointegratedserviceslimited@gmail.com</a>.
    </p>
@endsection
