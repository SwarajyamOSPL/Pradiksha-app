<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Number
    |--------------------------------------------------------------------------
    |
    | Invoices are numbered "{prefix}-{year}-{order id padded}", for example
    | INV-2026-000042, and the number is assigned when the payment is verified.
    |
    */

    'prefix' => env('INVOICE_PREFIX', 'INV'),

    /*
    |--------------------------------------------------------------------------
    | Seller Details
    |--------------------------------------------------------------------------
    |
    | Shown in the header of every invoice PDF. Set these in the .env file.
    |
    */

    'company' => [
        'name' => env('INVOICE_COMPANY_NAME', env('APP_NAME', 'Pradiksha Herbal')),
        'address' => env('INVOICE_COMPANY_ADDRESS'),
        'phone' => env('INVOICE_COMPANY_PHONE'),
        'email' => env('INVOICE_COMPANY_EMAIL'),
        'gstin' => env('INVOICE_COMPANY_GSTIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GST
    |--------------------------------------------------------------------------
    |
    | "rate" is the GST percentage applied to every order (0 hides the GST
    | line). When "prices_include_tax" is true the listed prices already
    | contain GST and the invoice shows how much of the total is tax. When
    | false, GST is added on top of the listed prices at checkout.
    |
    */

    'tax' => [
        'label' => env('INVOICE_TAX_LABEL', 'GST'),
        'rate' => (float) env('INVOICE_GST_RATE', 0),
        'prices_include_tax' => (bool) env('INVOICE_PRICES_INCLUDE_TAX', true),
    ],

    'footer_note' => env('INVOICE_FOOTER_NOTE', 'Thank you for your business.'),

];
