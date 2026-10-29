<x-mail::message>
# {{ __('Thank you for your order, :name!', ['name' => $order->user->name]) }}

{{ __('We have received your payment and your order is confirmed. Your invoice is attached to this email as a PDF.') }}

**{{ __('Order') }}:** #{{ $order->id }}<br>
**{{ __('Invoice') }}:** {{ $order->invoice_number }}<br>
**{{ __('Date') }}:** {{ $order->paid_at?->format('d M Y, h:i A') }}<br>
@if ($order->package)
**{{ __('Package') }}:** {{ $order->package->name }}<br>
@endif

<x-mail::table>
| {{ __('Product') }} | {{ __('Qty') }} | {{ __('Amount') }} |
| :------- | :---: | ------: |
@if ($order->itemsAreIncluded())
| **{{ $order->package->name }} {{ __('Package') }}** | 1 | ₹{{ number_format((float) $order->subtotal, 2) }} |
@endif
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ $order->itemsAreIncluded() ? __('Included') : '₹'.number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

@if ((float) $order->discount_amount > 0)
{{ __('Subtotal') }}: ₹{{ number_format((float) $order->subtotal, 2) }}<br>
{{ __('Discount') }}: -₹{{ number_format((float) $order->discount_amount, 2) }}<br>
@endif
@if ((float) $order->tax_amount > 0)
{{ config('invoice.tax.label') }} ({{ rtrim(rtrim(number_format((float) $order->tax_rate, 2), '0'), '.') }}%{{ $order->tax_inclusive ? ', '.__('included') : '' }}): ₹{{ number_format((float) $order->tax_amount, 2) }}<br>
@endif
**{{ __('Total paid') }}: ₹{{ number_format((float) $order->total_amount, 2) }}**

<x-mail::button :url="$orderUrl">
{{ __('View your order') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
