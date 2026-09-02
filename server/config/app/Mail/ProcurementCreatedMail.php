<?php

namespace App\Mail;

use App\Models\Procurement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcurementCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $procurement;

    public function __construct(Procurement $procurement)
    {
        $this->procurement = $procurement;
    }

    public function build()
    {
        $idStr = $this->procurement->procurement_id ?? ('PR-' . $this->procurement->id);
        return $this->subject("Procurement Request Confirmation [{$idStr}]")
                    ->view('emails.procurement_created');
    }
}
