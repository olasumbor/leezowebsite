<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $trackingId;

    public function __construct(Quote $quote, ?string $trackingId = null)
    {
        $this->quote = $quote;
        $this->trackingId = $trackingId;
    }

    public function build()
    {
        $ref = $this->trackingId ? $this->trackingId : "Q-{$this->quote->id}";
        return $this->subject("Freight Quote Request Received [{$ref}]")
                    ->view('emails.quote_submitted');
    }
}
