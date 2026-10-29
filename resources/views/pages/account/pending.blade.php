@php
    $user = auth()->user();
    $suspended = $user->status->value === 'suspended';
    $paidForPackage = $user->hasPaidForSignupPackage();
    $canPay = $user->canPayForSignupPackage();
@endphp

<x-layouts::auth :title="__('Account pending approval')">
    <div class="flex flex-col gap-6 text-center">
        <x-auth-header
            :title="$paidForPackage && ! $suspended ? __('Payment received') : $user->status->label()"
            :description="match (true) {
                $suspended => __('Your account has been suspended. Please contact Pradiksha Herbal support for assistance.'),
                $paidForPackage => __('Thank you! Your package payment is confirmed. Our admin will approve your account shortly, and you will then be able to log in and place orders.'),
                $canPay => __('Your account is created. Complete the payment for your package to finish.'),
                default => __('Thanks for registering! Your request has been sent to our admin for approval. You will be able to log in, see pricing and place orders once it is approved.'),
            }"
        />

        @if ($canPay)
            <flux:button variant="primary" icon="credit-card" :href="route('packages.checkout', $user->signupPackage)" class="w-full">
                {{ __('Continue to payment') }}
            </flux:button>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
