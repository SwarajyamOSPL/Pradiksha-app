@php
    $company = config('invoice.company.name') ?: config('app.name');

    $sections = [
        [
            'id' => 'who-we-are',
            'title' => __('Who we are'),
            'body' => [
                __('This policy applies to the :app website, where franchise partners buy packages and order products. In this policy, "we" and "us" mean :company.', ['app' => config('app.name'), 'company' => $company]),
            ],
        ],
        [
            'id' => 'information-we-collect',
            'title' => __('Information we collect'),
            'body' => [
                __('We collect only what we need to set up your franchise account and process your orders:'),
                [
                    __('Account and business details: your name, email address, phone number, franchise name and delivery address (address, city, state and pincode).'),
                    __('Order and payment records: the packages and products you buy, amounts, invoices, and the payment reference from our payment provider.'),
                    __('Sign-in information: your password, which we store only in a scrambled (hashed) form, and the cookies that keep you signed in.'),
                ],
                __('We never see or store your card, UPI or bank details. Payments are taken securely by Razorpay on its own payment window.'),
            ],
        ],
        [
            'id' => 'how-we-use-it',
            'title' => __('How we use your information'),
            'body' => [
                [
                    __('To create and manage your franchise account.'),
                    __('To take payment, process and deliver your orders, and issue invoices.'),
                    __('To send you emails about your account and orders, such as order confirmations and invoices.'),
                    __('To let your sales manager and our team support you.'),
                    __('To keep the website secure, prevent misuse and meet our legal and tax obligations.'),
                ],
            ],
        ],
        [
            'id' => 'who-we-share-with',
            'title' => __('Who we share it with'),
            'body' => [
                __('We share your information only where it is needed to serve you:'),
                [
                    __('Razorpay, our payment provider, to take your payment.'),
                    __('Courier and delivery partners, who receive your name, delivery address and phone number so they can deliver your order.'),
                    __('Your assigned sales manager and our admin team, who can see your business details, orders and shipping status.'),
                    __('Our email service, to send you the emails described above.'),
                    __('Government or legal authorities, if the law requires us to.'),
                ],
                __('We do not sell your personal information.'),
            ],
        ],
        [
            'id' => 'cookies',
            'title' => __('Cookies'),
            'body' => [
                __('We use only the cookies the website needs to work: to keep you signed in, to protect our forms from misuse and to remember what is in your cart. We do not use advertising or tracking cookies.'),
            ],
        ],
        [
            'id' => 'how-long-we-keep-it',
            'title' => __('How long we keep it'),
            'body' => [
                __('We keep your account details for as long as your account is active. We keep order, invoice and payment records for as long as we need them for accounting, tax and legal reasons.'),
            ],
        ],
        [
            'id' => 'keeping-it-safe',
            'title' => __('Keeping your information safe'),
            'body' => [
                __('Passwords are stored in hashed form, the website uses an encrypted connection, and only the people who need your information to do their job can see it. No system is completely secure, so please keep your password private.'),
            ],
        ],
        [
            'id' => 'your-choices',
            'title' => __('Your choices'),
            'body' => [
                __('You can update your name and email address from the settings in your account. To correct any other details, to see the information we hold about you, or to ask us to delete it, contact us. We will help, though we must keep the records we are legally required to hold, such as invoices.'),
            ],
        ],
        [
            'id' => 'children',
            'title' => __('Children'),
            'body' => [
                __('This website is for businesses and is not meant for anyone under 18.'),
            ],
        ],
    ];
@endphp

<x-policy-page
    :title="__('Privacy Policy')"
    :intro="__('This policy explains what personal information :name collects, why we collect it and how we look after it. By using the website, registering or placing an order you agree to it.', ['name' => config('app.name')])"
    :highlights="[
        __('We collect only what we need to run your franchise account and orders.'),
        __('We never see or store your card, UPI or bank details. Razorpay handles payments.'),
        __('We do not sell your personal information.'),
        __('We use only the cookies the website needs to work.'),
    ]"
    :sections="$sections"
    :contact-text="__('For any question about this policy, or to see, correct or delete your information, contact the company or your assigned sales manager, whose details are on your dashboard.')"
    :changes-text="__('We may update this policy from time to time. The latest version is always on this page, with the date it was last updated at the top.')"
/>
