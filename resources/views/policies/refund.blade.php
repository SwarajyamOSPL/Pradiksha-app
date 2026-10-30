@php
    $sections = [
        [
            'id' => 'packages',
            'title' => __('Package purchases'),
            'body' => [
                __('Your first purchase is the package you buy when you register as a franchise holder, whether that is the Basic, Mini or Super package.'),
                __('Once a package has been purchased, it will not be refunded. Please check what is included in each package on the Packages page before you pay.'),
            ],
        ],
        [
            'id' => 'cancellation',
            'title' => __('Cancelling an order'),
            'body' => [
                __('After you have bought a package and become a franchise holder, you can cancel any further order you place, within 24 hours of placing that order.'),
                __('To cancel, contact us within the 24 hours and quote your order number. An order that has already been dispatched cannot be cancelled.'),
                __('When a cancellation is accepted, the amount you paid for that order is refunded to the original payment method within 7 working days.'),
            ],
        ],
        [
            'id' => 'damaged',
            'title' => __('Broken or damaged products'),
            'body' => [
                __('If you receive any broken or damaged products, you must inform the company within 24 hours of receiving the goods. Broken or damaged goods will be replaced.'),
                __('When you contact us, please share your order number and clear photos or a video showing the damaged product and the outer packaging with its courier label. Replacements are sent at no extra charge once we have checked the report.'),
                __('If a replacement is not available, we will agree a suitable alternative with you, such as a credit or a refund for that item.'),
            ],
        ],
        [
            'id' => 'wrong-or-missing',
            'title' => __('Wrong or missing items'),
            'body' => [
                __('If an item in your delivery is not what you ordered, or something listed on your invoice is missing from the box, tell us within 24 hours of receiving the goods, with your order number and photos, and we will put it right.'),
            ],
        ],
        [
            'id' => 'expiry',
            'title' => __('Expired products'),
            'body' => [
                __('Products that reach their expiry date are not accepted as returns. Please check the expiry dates when your goods arrive and plan your stock accordingly.'),
            ],
        ],
        [
            'id' => 'not-accepted',
            'title' => __('What we cannot take back'),
            'body' => [
                __('Apart from the cases above, we cannot accept returns for a change of mind, for products that have been opened or used, or for products damaged after delivery through handling or storage.'),
            ],
        ],
        [
            'id' => 'payment-problems',
            'title' => __('Payment problems'),
            'body' => [
                __('If money was taken from your account but your order or package does not show as paid, contact us with your payment ID. We will confirm the payment and either complete your order or refund the amount within 7 working days.'),
            ],
        ],
    ];
@endphp

<x-policy-page
    :title="__('Refund & Return Policy')"
    :intro="__('This policy explains when :name can cancel, refund or replace an order. Please read it before you buy a package or place an order.', ['name' => config('app.name')])"
    :highlights="[
        __('A package purchase is final and is not refunded.'),
        __('Any later order can be cancelled within 24 hours of placing it.'),
        __('Report broken or damaged goods within 24 hours of receiving them and they will be replaced.'),
        __('Products that have expired are not accepted as returns.'),
    ]"
    :sections="$sections"
    :contact-text="__('For cancellations, damaged goods or any question about this policy, contact the company or your assigned sales manager, whose details are on your dashboard.')"
    :changes-text="__('We may update this policy from time to time. The policy shown on this page when you place an order is the one that applies to that order.')"
/>
