<x-layouts::storefront :title="__('Packages')">
    <div class="mx-auto max-w-6xl px-6 py-12">
        <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ __('Packages') }}</flux:heading>
        <flux:subheading class="mt-2 text-brand-700 dark:text-brand-300">{{ __('Curated product bundles put together by our team.') }}</flux:subheading>

        @unless (auth()->check() && auth()->user()->isApproved())
            <flux:callout icon="lock-closed" color="green" class="mt-4">
                <flux:callout.heading>{{ __('Prices are for franchise holders only') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Register and get approved to see franchise pricing and place orders.') }}
                </flux:callout.text>
            </flux:callout>
        @endunless

        <div class="mt-8 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($packages as $package)
                @include('packages._card', ['package' => $package])
            @empty
                <flux:text class="col-span-full">{{ __('No packages found.') }}</flux:text>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $packages->links() }}
        </div>
    </div>
</x-layouts::storefront>
