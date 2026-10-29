<x-layouts::storefront :title="__('Packages')">
    <div
        class="mx-auto max-w-6xl px-6 py-12"
        x-data="{
            open: null,
            toggle(id) {
                this.open = this.open === id ? null : id

                if (this.open !== null) {
                    this.$nextTick(() => document.getElementById('package-' + id + '-products')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
                }
            },
        }"
    >
        <div class="text-center">
            <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ __('Franchise Packages') }}</flux:heading>
            <flux:subheading class="mx-auto mt-2 max-w-2xl text-brand-700 dark:text-brand-300">
                {{ __('Pick the package that suits your business. Open a package to see every product inside it, then select it to register and pay.') }}
            </flux:subheading>
        </div>

        <div class="mt-10 grid items-start gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($packages as $package)
                @include('packages._card', ['package' => $package])
            @empty
                <flux:text class="col-span-full text-center">{{ __('No packages found.') }}</flux:text>
            @endforelse
        </div>

        {{-- The products of the opened package, in one panel below the cards: 4 across on desktop, 2 across on mobile. --}}
        @foreach ($packages as $package)
            @php
                $canChoose = ! auth()->check() || auth()->user()->isFranchiseHolder();
                $selectUrl = auth()->check() ? route('packages.checkout', $package) : route('register', ['package' => $package->slug]);
            @endphp

            <section
                id="package-{{ $package->id }}-products"
                x-show="open === {{ $package->id }}"
                x-collapse
                x-cloak
                class="scroll-mt-24"
                aria-label="{{ __(':name package products', ['name' => $package->name]) }}"
            >
                <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-100 dark:bg-brand-900 dark:ring-brand-800">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __("What's in the :name package", ['name' => $package->name]) }}</flux:heading>
                            <flux:text class="mt-1 text-brand-700 dark:text-brand-300">
                                {{ trans_choice(':count product|:count products', $package->products->count()) }},
                                {{ trans_choice(':count unit in total|:count units in total', $package->unitCount()) }}
                                &middot; ₹{{ number_format((float) $package->price, 0) }}
                            </flux:text>
                        </div>

                        @if ($canChoose && $package->isAvailable())
                            <flux:button variant="primary" icon-trailing="arrow-right" :href="$selectUrl" wire:navigate>
                                {{ __('Select the :name package', ['name' => $package->name]) }}
                            </flux:button>
                        @endif
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4" data-package-products="{{ $package->slug }}">
                        @foreach ($package->products as $product)
                            <div class="flex flex-col gap-2" wire:key="package-{{ $package->id }}-product-{{ $product->id }}">
                                <div class="relative aspect-square overflow-hidden rounded-xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-950 dark:ring-brand-800">
                                    @if ($product->image_path)
                                        <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-brand-300 dark:text-brand-700">
                                            <flux:icon.photo class="size-10" />
                                        </div>
                                    @endif

                                    <flux:badge size="sm" color="green" variant="solid" class="absolute top-2 right-2">&times;{{ $product->pivot->quantity }}</flux:badge>
                                </div>

                                <p class="line-clamp-2 text-sm font-medium text-brand-950 dark:text-cream-50">{{ $product->name }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        <div class="mt-8">
            {{ $packages->links() }}
        </div>
    </div>
</x-layouts::storefront>
