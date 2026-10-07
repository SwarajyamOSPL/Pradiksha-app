@php $user = auth()->user(); @endphp

<flux:callout>
    <flux:callout.heading>{{ __('Shipping to') }}</flux:callout.heading>
    <flux:callout.text>
        {{ $user->business_name }}<br>
        {{ $user->address }}, {{ $user->city }}, {{ $user->state }} {{ $user->pincode }}<br>
        {{ $user->phone }}
    </flux:callout.text>
</flux:callout>
