@extends('emails.layout', ['title' => 'Subscribed to Leezofood Newsletter'])

@section('content')
    <h2>Welcome to the Leezofood Newsletter!</h2>
    <p>Thank you for subscribing to the <strong>Leezofood NG.Export</strong> newsletter.</p>
    <p>You will now receive our latest updates on global freight schedules, export logistics tips, shipping rates, and exclusive promotional offers straight to your inbox.</p>
    
    <div class="info-box">
        <p><strong>Subscribed Email:</strong> {{ $subscriber->email }}</p>
    </div>

    <p>Stay tuned for our upcoming updates!</p>
@endsection
