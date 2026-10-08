<x-layouts::storefront>
    <x-hero-carousel :slides="[
        ['src' => asset('images/sliders/pradiksha-range.webp'), 'alt' => __('Pradiksha Complete Therapy Range')],
        ['src' => asset('images/sliders/metabolicrange.webp'), 'alt' => __('Pradiksha Metabolic Care Range')],
        ['src' => asset('images/sliders/therapy-range.webp'), 'alt' => __('Pradiksha Therapy Solutions')],
    ]" />

    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-100 via-cream-50 to-cream-50 dark:from-brand-900 dark:via-brand-950 dark:to-brand-950"></div>
        <div class="absolute -top-24 -right-24 size-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-700/20"></div>
        <div class="absolute -bottom-32 -left-24 size-96 rounded-full bg-brand-300/30 blur-3xl dark:bg-brand-800/20"></div>

        <div class="relative mx-auto max-w-6xl px-6 py-20 text-center sm:py-28">
            <flux:badge class="border border-brand-200 bg-white/70! text-brand-800! dark:border-brand-700 dark:bg-brand-900/60! dark:text-brand-200!">
                🌿 {{ __('Franchise Opportunity') }}
            </flux:badge>

            <flux:heading class="mt-6 text-4xl font-semibold tracking-tight text-brand-950 sm:text-5xl dark:text-cream-50">
                {{ __('Grow a herbal wellness business with :name', ['name' => config('app.name')]) }}
            </flux:heading>

            <flux:subheading class="mx-auto mt-5 max-w-2xl text-lg text-brand-700 dark:text-brand-200">
                {{ __('Become an authorised franchise holder, get access to franchise pricing on our full ayurvedic product range, and order curated packages or build your own.') }}
            </flux:subheading>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <flux:button :href="route('products.index')" variant="primary" icon="shopping-bag" wire:navigate>
                    {{ __('Browse Products') }}
                </flux:button>

                @guest
                    <flux:button :href="route('register')" variant="filled" wire:navigate>
                        {{ __('Become a Franchise Partner') }}
                    </flux:button>
                @endguest
            </div>
        </div>
    </section>

    @if ($featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-6xl px-6 pb-20">
            <div class="flex items-baseline justify-between">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('Featured Products') }}</flux:heading>
                <flux:link :href="route('products.index')" wire:navigate>{{ __('View all') }} &rarr;</flux:link>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    @include('products._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    <section class="border-t border-brand-100 bg-brand-50 py-16 dark:border-brand-800 dark:bg-brand-900/40">
        <div class="mx-auto max-w-6xl px-6">
            <flux:heading size="lg" class="text-center text-brand-950 dark:text-cream-50">{{ __('How franchise ordering works') }}</flux:heading>

            <div class="mt-10 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'pencil-square', 'title' => __('Register'), 'text' => __('Sign up with your business details in a couple of minutes.')],
                    ['icon' => 'check-badge', 'title' => __('Get Approved'), 'text' => __('Our team reviews and approves your franchise account.')],
                    ['icon' => 'truck', 'title' => __('Start Ordering'), 'text' => __('See franchise pricing, order curated packages, or build your own basket.')],
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
</x-layouts::storefront>
