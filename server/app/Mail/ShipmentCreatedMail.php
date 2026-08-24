<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;

    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    public function build()
    {
        return $this->subject("Your Shipment is Ready - Tracking ID: {$this->shipment->tracking_id}")
                    ->view('emails.shipment_created');
    }
}
