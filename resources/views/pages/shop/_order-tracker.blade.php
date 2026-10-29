@php
    use App\Enums\OrderStatus;

    $steps = [
        OrderStatus::Confirmed->value => __('Confirmed'),
        OrderStatus::Processing->value => __('Packing'),
        OrderStatus::Shipped->value => __('In transit'),
        OrderStatus::Delivered->value => __('Delivered'),
    ];
    $currentIndex = array_search($order->status->value, array_keys($steps), true);
@endphp

@if ($order->status === OrderStatus::Cancelled)
    <flux:callout icon="x-circle" color="red" class="mt-6">
        <flux:callout.heading>{{ __('This order was cancelled') }}</flux:callout.heading>
    </flux:callout>
@elseif ($order->isPaid() && $currentIndex !== false)
    <div class="mt-6 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">{{ __('Order status') }}</flux:heading>
            <flux:text size="sm" class="font-medium">{{ __($order->status->customerMessage()) }}</flux:text>
        </div>

        <ol class="mt-5 flex items-start" aria-label="{{ __('Order progress') }}">
            @foreach (array_values($steps) as $index => $label)
                @php
                    $done = $index < $currentIndex || $order->status === OrderStatus::Delivered;
                    $current = $index === $currentIndex && $order->status !== OrderStatus::Delivered;
                @endphp
                <li class="relative flex flex-1 flex-col items-center text-center" @if ($current) aria-current="step" @endif>
                    @unless ($loop->first)
                        <span class="absolute start-[-50%] top-4 -z-0 h-0.5 w-full {{ $done || $current ? 'bg-brand-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></span>
                    @endunless

                    <span class="relative z-10 flex size-8 items-center justify-center rounded-full text-sm font-semibold
                        {{ $done ? 'bg-brand-600 text-white' : ($current ? 'bg-brand-100 text-brand-700 ring-2 ring-brand-500 dark:bg-brand-900 dark:text-brand-200' : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400') }}">
                        @if ($done)
                            <flux:icon.check class="size-4" />
                        @elseif ($current && $order->status === OrderStatus::Shipped)
                            <flux:icon.truck class="size-4" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>

                    <span class="mt-2 text-xs {{ $done || $current ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    </div>
@endif
