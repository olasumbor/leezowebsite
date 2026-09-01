<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderType;
    public $orderId;
    public $customerName;
    public $detailsUrl;

    public function __construct($order, string $orderType = 'Shipment')
    {
        $this->order = $order;
        $this->orderType = $orderType;
        
        $this->orderId = $order->tracking_id ?? $order->procurement_id ?? $order->request_id ?? $order->id;
        $this->customerName = $order->recipient_name ?? $order->name ?? ($order->user->name ?? 'Valued Customer');
        
        $frontendUrl = config('app.frontend_url', 'http://localhost:8080');

        switch (strtolower($orderType)) {
            case 'procurement':
                $this->detailsUrl = "{$frontendUrl}/procurement-details.html?id={$this->orderId}";
                break;
            case 'pickup & delivery':
            case 'pickup_delivery':
                $this->detailsUrl = "{$frontendUrl}/pickup-delivery-details.html?id={$this->orderId}";
                break;
            case 'frozen cargo':
            case 'frozen_cargo':
                $this->detailsUrl = "{$frontendUrl}/frozen-cargo-details.html?id={$this->orderId}";
                break;
            default:
                $this->detailsUrl = "{$frontendUrl}/shipment-details.html?id={$this->orderId}";
                break;
        }
    }

    public function build()
    {
        return $this->subject("Official Invoice Generated: {$this->orderType} #{$this->orderId}")
                    ->view('emails.invoice_generated');
    }
}
