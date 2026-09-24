<?php

namespace App\Mail;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;
    public $latestEvent;

    public function __construct(Shipment $shipment, ?ShipmentEvent $latestEvent = null)
    {
        $this->shipment = $shipment;
        $this->latestEvent = $latestEvent;
    }

    public function build()
    {
        $statusStr = strtoupper($this->shipment->status);
        return $this->subject("Tracking Update: Shipment {$this->shipment->tracking_id} - {$statusStr}")
                    ->view('emails.shipment_updated');
    }
}
