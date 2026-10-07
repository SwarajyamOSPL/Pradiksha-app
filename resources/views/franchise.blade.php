<x-layouts::storefront :title="__('Franchise Model')">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-100 via-cream-50 to-cream-50 dark:from-brand-900 dark:via-brand-950 dark:to-brand-950"></div>
        <div class="absolute -top-24 -right-24 size-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-700/20"></div>
        <div class="absolute -bottom-32 -left-24 size-96 rounded-full bg-brand-300/30 blur-3xl dark:bg-brand-800/20"></div>

        <div class="relative mx-auto max-w-6xl px-6 py-20 text-center sm:py-28">
            <flux:badge class="border border-brand-200 bg-white/70! text-brand-800! dark:border-brand-700 dark:bg-brand-900/60! dark:text-brand-200!">
                🌿 {{ __('Franchise Model') }}
            </flux:badge>

            <flux:heading class="mt-6 text-4xl font-semibold tracking-tight text-brand-950 sm:text-5xl dark:text-cream-50">
                {{ __('Partner with :name', ['name' => config('app.name')]) }}
            </flux:heading>

            <flux:subheading class="mx-auto mt-5 max-w-2xl text-lg text-brand-700 dark:text-brand-200">
                {{ __('Bring authentic ayurvedic and herbal wellness products to your customers. As an approved franchise partner you get franchise pricing, curated packages and a dedicated team behind you.') }}
            </flux:subheading>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @guest
                    <flux:button :href="route('register')" variant="primary" icon="pencil-square" wire:navigate>
                        {{ __('Become a Franchise Partner') }}
                    </flux:button>
                    <flux:button :href="route('login')" variant="filled" wire:navigate>
                        {{ __('Log in') }}
                    </flux:button>
                @else
                    <flux:button :href="route('products.index')" variant="primary" icon="shopping-bag" wire:navigate>
                        {{ __('Browse Products') }}
                    </flux:button>
                    <flux:button :href="route('packages.index')" variant="filled" wire:navigate>
                        {{ __('View Packages') }}
                    </flux:button>
                @endguest
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 pb-20">
        <div class="text-center">
            <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ __('What you get as a franchise partner') }}</flux:heading>
            <flux:text class="mx-auto mt-3 max-w-2xl text-brand-700 dark:text-brand-200">
                {{ __('Everything you need to start selling, in one place.') }}
            </flux:text>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'tag', 'title' => __('Franchise pricing'), 'text' => __('Approved partners see exclusive franchise pricing across the full product range. Prices are never shown publicly.')],
                ['icon' => 'archive-box', 'title' => __('Curated packages'), 'text' => __('Order ready-made product packages put together by our team, so you can stock a balanced range in a single order.')],
                ['icon' => 'shopping-bag', 'title' => __('Build your own basket'), 'text' => __('Prefer to choose? Pick individual products and quantities to match what your customers ask for.')],
                ['icon' => 'identification', 'title' => __('A dedicated sales manager'), 'text' => __('Every partner is assigned a sales manager who can guide you on products and help with your orders.')],
                ['icon' => 'sparkles', 'title' => __('Authentic ayurvedic range'), 'text' => __('Herbal oils, syrups, powders, supplements and more, all from the Pradiksha Herbal catalogue.')],
                ['icon' => 'receipt-percent', 'title' => __('Simple online ordering'), 'text' => __('Place orders online and follow every order from confirmation to delivery in your account.')],
            ] as $benefit)
                <flux:card class="border-brand-100 bg-white dark:border-brand-800 dark:bg-brand-950">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                        <flux:icon :icon="$benefit['icon']" class="size-6" />
                    </div>
                    <flux:heading size="lg" class="mt-4 text-brand-950 dark:text-cream-50">{{ $benefit['title'] }}</flux:heading>
                    <flux:text class="mt-2 text-brand-700 dark:text-brand-200">{{ $benefit['text'] }}</flux:text>
                </flux:card>
            @endforeach
        </div>
    </section>

    <section class="border-y border-brand-100 bg-brand-50 py-16 dark:border-brand-800 dark:bg-brand-900/40">
        <div class="mx-auto max-w-6xl px-6">
            <flux:heading size="xl" class="text-center text-brand-950 dark:text-cream-50">{{ __('How it works') }}</flux:heading>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'pencil-square', 'title' => __('Register'), 'text' => __('Create your account with your business details in a couple of minutes.')],
                    ['icon' => 'check-badge', 'title' => __('Get approved'), 'text' => __('Our team reviews your application and approves your franchise account.')],
                    ['icon' => 'user-group', 'title' => __('Meet your manager'), 'text' => __('You are assigned a sales manager who supports you from day one.')],
                    ['icon' => 'truck', 'title' => __('Order and grow'), 'text' => __('See franchise pricing, order packages or a custom basket, and track your orders.')],
                ] as $index => $step)
                    <flux:card class="border-brand-100 bg-white text-center dark:border-brand-800 dark:bg-brand-950">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand-600 text-white">
                            <flux:icon :icon="$step['icon']" class="size-6" />
                        </div>
                        <flux:heading size="lg" class="mt-4 text-brand-950 dark:text-cream-50">{{ $index + 1 }}. {{ $step['title'] }}</flux:heading>
                        <flux:text class="mt-2 text-brand-700 dark:text-brand-200">{{ $step['text'] }}</flux:text>
                    </flux:card>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-20">
        <flux:heading size="xl" class="text-center text-brand-950 dark:text-cream-50">{{ __('Two ways to order') }}</flux:heading>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <flux:card class="border-brand-100 bg-white dark:border-brand-800 dark:bg-brand-950">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('Order a package') }}</flux:heading>
                <flux:text class="mt-2 text-brand-700 dark:text-brand-200">
                    {{ __('Choose a curated package and order it as-is. A quick way to stock a ready-made selection of products.') }}
                </flux:text>
                <flux:button :href="route('packages.index')" variant="primary" class="mt-5" wire:navigate>{{ __('View Packages') }}</flux:button>
            </flux:card>

            <flux:card class="border-brand-100 bg-white dark:border-brand-800 dark:bg-brand-950">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('Build a custom basket') }}</flux:heading>
                <flux:text class="mt-2 text-brand-700 dark:text-brand-200">
                    {{ __('Pick individual products and quantities to match your customers. Ideal when you know exactly what sells.') }}
                </flux:text>
                <flux:button :href="route('products.index')" variant="primary" class="mt-5" wire:navigate>{{ __('Browse Products') }}</flux:button>
            </flux:card>
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-6 pb-20">
        <flux:heading size="xl" class="text-center text-brand-950 dark:text-cream-50">{{ __('Common questions') }}</flux:heading>

        <div class="mt-8 divide-y divide-brand-100 rounded-xl border border-brand-100 bg-white dark:divide-brand-800 dark:border-brand-800 dark:bg-brand-950">
            @foreach ([
                ['q' => __('Why can I not see prices?'), 'a' => __('Prices are shown only to approved franchise partners. Browse the catalogue freely, then register and get approved to see your franchise pricing.')],
                ['q' => __('How long does approval take?'), 'a' => __('Our team reviews each application manually. You will be able to see pricing and place orders as soon as your account is approved.')],
                ['q' => __('Can I order individual products?'), 'a' => __('Yes. You can order a curated package as-is, or build your own basket from individual products.')],
                ['q' => __('Who can I contact for help?'), 'a' => __('Once approved, you are assigned a sales manager who can help with products and orders.')],
            ] as $faq)
                <details class="group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-brand-950 dark:text-cream-50">
                        {{ $faq['q'] }}
                        <flux:icon name="chevron-down" class="size-5 shrink-0 text-brand-600 transition group-open:rotate-180 dark:text-brand-300" />
                    </summary>
                    <flux:text class="mt-3 text-brand-700 dark:text-brand-200">{{ $faq['a'] }}</flux:text>
                </details>
            @endforeach
        </div>
    </section>

    @guest
        <section class="mx-auto max-w-6xl px-6">
            <div class="rounded-2xl bg-brand-700 px-6 py-12 text-center text-white sm:px-12 dark:bg-brand-800">
                <flux:heading size="xl" class="text-white">{{ __('Ready to become a franchise partner?') }}</flux:heading>
                <p class="mx-auto mt-3 max-w-xl text-brand-100">{{ __('Register today and our team will review your application.') }}</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
                    <flux:button :href="route('register')" variant="filled" class="bg-white! text-brand-800!" wire:navigate>{{ __('Register now') }}</flux:button>
                    <flux:button :href="route('login')" variant="ghost" class="text-white!" wire:navigate>{{ __('I already have an account') }}</flux:button>
                </div>
            </div>
        </section>
    @endguest
</x-layouts::storefront>
