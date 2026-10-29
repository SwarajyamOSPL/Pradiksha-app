@props(['package'])

@php
    $isAvailable = $package->isAvailable();

    // Basic, Mini and Super get their own look; any other package uses the default green.
    $tier = match (strtolower($package->name)) {
        'basic' => ['header' => 'from-brand-400 to-brand-600', 'label' => 'text-brand-100', 'ring' => 'ring-brand-200 dark:ring-brand-700'],
        'mini' => ['header' => 'from-brand-600 to-brand-800', 'label' => 'text-brand-100', 'ring' => 'ring-brand-300 dark:ring-brand-600'],
        'super' => ['header' => 'from-brand-800 to-brand-950', 'label' => 'text-amber-300', 'ring' => 'ring-amber-300 dark:ring-amber-500'],
        default => ['header' => 'from-brand-500 to-brand-700', 'label' => 'text-brand-100', 'ring' => 'ring-brand-200 dark:ring-brand-700'],
    };

    $canChoose = ! auth()->check() || auth()->user()->isFranchiseHolder();
    $selectUrl = auth()->check() ? route('packages.checkout', $package) : route('register', ['package' => $package->slug]);
@endphp

{{-- Lives inside the packages page's x-data scope: "open" holds the id of the package whose products are showing. --}}
<div
    class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-2 transition dark:bg-brand-900"
    x-bind:class="open === {{ $package->id }} ? 'ring-brand-600 shadow-lg dark:ring-brand-300' : '{{ $tier['ring'] }}'"
    data-package="{{ $package->slug }}"
>
    <div class="bg-gradient-to-br {{ $tier['header'] }} px-6 pt-6 pb-7 text-white">
        <p class="text-xs font-semibold tracking-widest uppercase {{ $tier['label'] }}">{{ __('Package') }}</p>
        <h2 class="mt-1 text-4xl font-semibold tracking-tight">{{ $package->name }}</h2>

        <p class="mt-5 text-4xl font-semibold tracking-tight">₹{{ number_format((float) $package->price, 0) }}</p>
        <p class="mt-1 text-sm text-white/80">
            {{ trans_choice(':count product|:count products', $package->products->count()) }}
            &middot; {{ trans_choice(':count unit|:count units', $package->unitCount()) }}
        </p>
    </div>

    <div class="flex flex-col gap-3 p-5">
        <div class="flex flex-wrap gap-3">
            <flux:button
                type="button"
                class="grow"
                x-on:click="toggle({{ $package->id }})"
                x-bind:aria-expanded="(open === {{ $package->id }}).toString()"
                aria-controls="package-{{ $package->id }}-products"
            >
                <span x-show="open !== {{ $package->id }}">{{ __('Explore Package') }}</span>
                <span x-show="open === {{ $package->id }}" x-cloak>{{ __('Hide products') }}</span>
            </flux:button>

            @if ($canChoose)
                @if ($isAvailable)
                    <flux:button variant="primary" icon-trailing="arrow-right" class="grow" :href="$selectUrl" wire:navigate>
                        {{ __('Select') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" class="grow" disabled>{{ __('Out of stock') }}</flux:button>
                @endif
            @endif
        </div>

        @unless ($isAvailable)
            <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ __('This package is out of stock right now.') }}</flux:text>
        @endunless
    </div>
</div>
