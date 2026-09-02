<?php

namespace App\Mail;

use App\Models\PickupDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PickupDeliveryCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestItem;

    public function __construct(PickupDelivery $requestItem)
    {
        $this->requestItem = $requestItem;
    }

    public function build()
    {
        return $this->subject("Pick & Delivery Request Confirmed [{$this->requestItem->request_id}]")
                    ->view('emails.pickup_delivery_created');
    }
}
