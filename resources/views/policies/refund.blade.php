<x-layouts::storefront :title="__('Refund & Return Policy')">
    @php
        $company = config('invoice.company');

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

    <div class="mx-auto max-w-3xl px-6 py-12">
        <flux:heading size="xl" level="1" class="text-brand-950 dark:text-cream-50">{{ __('Refund & Return Policy') }}</flux:heading>
        <flux:text class="mt-2 text-brand-600 dark:text-brand-300">{{ __('Last updated October 2026') }}</flux:text>

        <flux:text class="mt-6 text-brand-700 dark:text-brand-200">
            {{ __('This policy explains when :name can cancel, refund or replace an order. Please read it before you buy a package or place an order.', ['name' => config('app.name')]) }}
        </flux:text>

        <flux:card class="mt-8 border-brand-100 bg-brand-50 dark:border-brand-800 dark:bg-brand-900/50">
            <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('At a glance') }}</flux:heading>
            <ul class="mt-4 space-y-3">
                @foreach ([
                    __('A package purchase is final and is not refunded.'),
                    __('Any later order can be cancelled within 24 hours of placing it.'),
                    __('Report broken or damaged goods within 24 hours of receiving them and they will be replaced.'),
                    __('Products that have expired are not accepted as returns.'),
                ] as $point)
                    <li class="flex items-start gap-3 text-brand-800 dark:text-brand-100">
                        <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-300" />
                        <span>{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        </flux:card>

        <div class="mt-10 space-y-10">
            @foreach ($sections as $number => $section)
                <section id="{{ $section['id'] }}" class="scroll-mt-24">
                    <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ $number + 1 }}. {{ $section['title'] }}</flux:heading>

                    <div class="mt-3 space-y-3">
                        @foreach ($section['body'] as $paragraph)
                            <flux:text class="text-brand-700 dark:text-brand-200">{{ $paragraph }}</flux:text>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section id="contact" class="scroll-mt-24">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ count($sections) + 1 }}. {{ __('How to contact us') }}</flux:heading>

                <div class="mt-3 space-y-3">
                    <flux:text class="text-brand-700 dark:text-brand-200">
                        {{ __('For cancellations, damaged goods or any question about this policy, contact the company or your assigned sales manager, whose details are on your dashboard.') }}
                    </flux:text>

                    @if (filled($company['email']) || filled($company['phone']))
                        <ul class="space-y-1 text-brand-800 dark:text-brand-100">
                            @if (filled($company['email']))
                                <li>{{ __('Email') }}: <a href="mailto:{{ $company['email'] }}" class="font-medium text-brand-600 hover:underline dark:text-brand-300">{{ $company['email'] }}</a></li>
                            @endif
                            @if (filled($company['phone']))
                                <li>{{ __('Phone') }}: <a href="tel:{{ $company['phone'] }}" class="font-medium text-brand-600 hover:underline dark:text-brand-300">{{ $company['phone'] }}</a></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </section>

            <section id="changes" class="scroll-mt-24">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ count($sections) + 2 }}. {{ __('Changes to this policy') }}</flux:heading>
                <flux:text class="mt-3 text-brand-700 dark:text-brand-200">
                    {{ __('We may update this policy from time to time. The policy shown on this page when you place an order is the one that applies to that order.') }}
                </flux:text>
            </section>
        </div>
    </div>
</x-layouts::storefront>
