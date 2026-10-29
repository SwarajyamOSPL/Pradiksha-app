<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderConfirmationEmail
{
    /**
     * Email the customer their confirmation and invoice. A mail failure is
     * logged but never undoes a payment that has already been recorded.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            Mail::to($event->order->user)->send(new OrderConfirmationMail($event->order));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
