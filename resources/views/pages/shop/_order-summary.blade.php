<div class="mt-4 flex flex-col gap-2 text-sm">
    @if ($totals->discount > 0 || $totals->tax > 0)
        <div class="flex items-center justify-between">
            <flux:text>{{ __('Subtotal') }}</flux:text>
            <flux:text>₹{{ number_format($totals->subtotal, 2) }}</flux:text>
        </div>
    @endif

    @if ($totals->discount > 0)
        <div class="flex items-center justify-between">
            <flux:text class="text-green-600 dark:text-green-400">{{ __('Discount') }}</flux:text>
            <flux:text class="text-green-600 dark:text-green-400">-₹{{ number_format($totals->discount, 2) }}</flux:text>
        </div>
    @endif

    @if ($totals->tax > 0)
        <div class="flex items-center justify-between">
            <flux:text>
                {{ config('invoice.tax.label') }} ({{ rtrim(rtrim(number_format($totals->taxRate, 2), '0'), '.') }}%{{ $totals->taxInclusive ? ', '.__('included') : '' }})
            </flux:text>
            <flux:text>₹{{ number_format($totals->tax, 2) }}</flux:text>
        </div>
    @endif

    <div class="mt-1 flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Total to pay') }}</flux:heading>
        <flux:heading size="lg">₹{{ number_format($totals->total, 2) }}</flux:heading>
    </div>
</div>
