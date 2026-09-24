<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NewsletterSubscribedMail;

class NewsletterController extends Controller
{
    // Public: Subscribe to newsletter
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $request->email],
            ['status' => 'subscribed']
        );

        try {
            Mail::to($subscriber->email)->send(new NewsletterSubscribedMail($subscriber));
        } catch (\Throwable $e) {
            Log::error("Failed to send NewsletterSubscribedMail to {$subscriber->email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Successfully subscribed to the newsletter!',
            'subscriber' => $subscriber
        ], 201);
    }

    // Admin: List all subscribers
    public function index()
    {
        $subscribers = NewsletterSubscriber::orderBy('created_at', 'desc')->get();
        return response()->json($subscribers);
    }
}
