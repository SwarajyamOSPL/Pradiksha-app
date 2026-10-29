<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Approve On Package Payment
    |--------------------------------------------------------------------------
    |
    | Someone who signs up by choosing a package can log in straight away to pay
    | for it. When this is true their account is approved automatically as soon
    | as that payment succeeds. When false they stay "pending" after paying and
    | an admin still has to approve them.
    |
    */

    'auto_approve_on_package_payment' => (bool) env('AUTO_APPROVE_ON_PACKAGE_PAYMENT', true),

];
