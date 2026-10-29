<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{-- Confirmations and errors from plain form posts, such as adding a product to the cart. --}}
        @if ((session('status') && session('status') !== 'verification-link-sent') || $errors->any())
            <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition>
                @if ($errors->any())
                    <flux:callout icon="exclamation-triangle" color="red" role="alert">
                        <flux:callout.heading>{{ $errors->first() }}</flux:callout.heading>
                    </flux:callout>
                @else
                    <flux:callout icon="check-circle" color="green" role="status">
                        <flux:callout.heading>{{ session('status') }}</flux:callout.heading>
                    </flux:callout>
                @endif
            </div>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
