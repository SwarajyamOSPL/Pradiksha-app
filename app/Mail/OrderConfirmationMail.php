<?php

namespace App\Mail;

use App\Actions\GenerateInvoicePdf;
use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderConfirmationMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Order #:id confirmed - Invoice :invoice', [
                'id' => $this->order->id,
                'invoice' => $this->order->invoice_number,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.confirmation',
            with: [
                'order' => $this->order->loadMissing('items', 'user', 'package'),
                'orderUrl' => route('orders.show', $this->order),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => app(GenerateInvoicePdf::class)($this->order), "{$this->order->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
