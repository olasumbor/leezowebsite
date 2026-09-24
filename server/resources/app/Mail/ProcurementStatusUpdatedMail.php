<?php

namespace App\Mail;

use App\Models\Procurement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcurementStatusUpdatedMail extends Mailable
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
        $statusStr = strtoupper($this->procurement->status);
        return $this->subject("Update on Procurement Request [{$idStr}] - Status: {$statusStr}")
                    ->view('emails.procurement_updated');
    }
}
