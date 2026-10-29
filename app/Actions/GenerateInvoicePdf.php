<?php

namespace App\Actions;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateInvoicePdf
{
    /**
     * Render the invoice for a paid order and return the raw PDF bytes.
     */
    public function __invoke(Order $order): string
    {
        $order->loadMissing('items', 'user', 'package');

        return Pdf::loadView('invoices.invoice', [
            'order' => $order,
            'company' => config('invoice.company'),
            'tax' => config('invoice.tax'),
            'footerNote' => config('invoice.footer_note'),
        ])->setPaper('a4')->setOption(['enable_font_subsetting' => true])->output();
    }
}
