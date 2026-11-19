<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlaced extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.order_placed_subject', ['number' => $this->order->number]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-placed');
    }
}
