<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactAdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    public function build()
    {
        $subj = $this->contactMessage->subject ?? 'Enquiry';
        return $this->subject("New Website Contact: {$subj} [MSG-{$this->contactMessage->id}]")
                    ->view('emails.contact_admin_notification');
    }
}
