<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactAcknowledgmentMail;
use App\Mail\ContactAdminNotificationMail;

class ContactController extends Controller
{
    // Public: Submit a contact message
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'subject' => 'nullable|string',
            'message' => 'required|string',
        ]);

        $contactMessage = ContactMessage::create($request->all());

        try {
            Mail::to($contactMessage->email)->send(new ContactAcknowledgmentMail($contactMessage));
            $adminEmail = env('MAIL_FROM_ADDRESS', 'info@leezofood.ng');
            try {
                Mail::to($adminEmail)->send(new ContactAdminNotificationMail($contactMessage));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Mail send error: " . $e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send contact emails for MSG-{$contactMessage->id}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Message sent successfully. We will get back to you shortly.',
            'data' => $contactMessage
        ], 201);
    }

    // Admin: List all messages
    public function index()
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->get();
        return response()->json($messages);
    }
}
