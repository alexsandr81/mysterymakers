<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Письмо владельцу магазина о новом заказе (всегда на русском) */
class NewOrderAdmin extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Новый заказ №' . $this->order->number . ' на ' . Money::format($this->order->total) . ' грн');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-order-admin');
    }
}
