<?php

namespace App\Http\Controllers;

use App\Actions\GenerateInvoicePdf;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    /**
     * Download the PDF invoice for a paid order. Customers can only fetch their own.
     */
    public function __invoke(Request $request, Order $order, GenerateInvoicePdf $generateInvoicePdf): Response
    {
        abort_unless($request->user()->isAdmin() || $order->user_id === $request->user()->id, 403);
        abort_unless($order->isPaid() && $order->invoice_number, 404);

        return response($generateInvoicePdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$order->invoice_number.'.pdf"',
        ]);
    }
}
