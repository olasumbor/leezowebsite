<?php

namespace App\Mail;

use App\Models\PickupDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PickupDeliveryStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pickupDelivery;

    public function __construct(PickupDelivery $pickupDelivery)
    {
        $this->pickupDelivery = $pickupDelivery;
    }

    public function build()
    {
        $idStr = $this->pickupDelivery->request_id ?? ('PKD-' . $this->pickupDelivery->id);
        $statusStr = strtoupper($this->pickupDelivery->status ?? 'PENDING');
        return $this->subject("Update on Pick & Delivery Request [{$idStr}] - Status: {$statusStr}")
                    ->view('emails.pickup_delivery_updated');
    }
}
