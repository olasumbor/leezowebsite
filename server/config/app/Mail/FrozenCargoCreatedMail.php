<?php

namespace App\Mail;

use App\Models\FrozenCargo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FrozenCargoCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestItem;

    public function __construct(FrozenCargo $requestItem)
    {
        $this->requestItem = $requestItem;
    }

    public function build()
    {
        return $this->subject("Frozen Cargo Request Received [{$this->requestItem->request_id}]")
                    ->view('emails.frozen_cargo_created');
    }
}
