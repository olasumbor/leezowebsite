<?php

namespace App\Mail;

use App\Models\FrozenCargo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FrozenCargoStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $frozenCargo;

    public function __construct(FrozenCargo $frozenCargo)
    {
        $this->frozenCargo = $frozenCargo;
    }

    public function build()
    {
        $idStr = $this->frozenCargo->request_id ?? ('RQST-' . $this->frozenCargo->id);
        $statusStr = strtoupper($this->frozenCargo->status ?? 'PENDING');
        return $this->subject("Update on Frozen Cargo Request [{$idStr}] - Status: {$statusStr}")
                    ->view('emails.frozen_cargo_updated');
    }
}
