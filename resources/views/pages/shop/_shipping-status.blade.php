@if ($order->isPaid())
    <flux:card class="mt-6">
        <flux:heading size="lg">{{ __('Shipping') }}</flux:heading>

        <dl class="mt-4 grid gap-6 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Delivery address') }}</dt>
                <dd class="mt-1 font-medium text-zinc-900 dark:text-white">
                    {{ $order->user->business_name ?: $order->user->name }}<br>
                    {{ collect([$order->user->address, $order->user->city, $order->user->state, $order->user->pincode])->filter()->join(', ') }}
                    @if ($order->user->phone)
                        <br>{{ $order->user->phone }}
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Shipment tracking') }}</dt>
                <dd class="mt-1">
                    @if ($order->isDispatched())
                        <p class="font-medium text-zinc-900 dark:text-white">
                            @if ($order->courier_name)
                                {{ $order->courier_name }}<br>
                            @endif
                            {{ __('AWB number') }}: <span class="font-mono">{{ $order->awb_number }}</span>
                        </p>
                        @if ($order->dispatched_at)
                            <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Dispatched on :date', ['date' => $order->dispatched_at->format('d M Y')]) }}</p>
                        @endif
                        @if ($order->tracking_url)
                            <flux:button size="sm" variant="primary" icon-trailing="arrow-top-right-on-square" class="mt-3" :href="$order->tracking_url" target="_blank" rel="noopener noreferrer">
                                {{ __('Track shipment') }}
                            </flux:button>
                        @endif
                    @else
                        <p class="text-zinc-600 dark:text-zinc-300">
                            {{ __('Not dispatched yet. The AWB number and tracking link will appear here as soon as your order is dispatched.') }}
                        </p>
                    @endif
                </dd>
            </div>
        </dl>
    </flux:card>
@endif
