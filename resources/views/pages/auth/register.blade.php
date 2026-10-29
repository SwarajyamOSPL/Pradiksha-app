@php
    // A package chosen on the packages page, passed along as ?package=slug (and kept through validation errors).
    $chosenPackage = \App\Models\Package::query()->active()->with('products')->where('slug', (string) request('package', old('package')))->first();
    $selectedPackage = $chosenPackage?->isAvailable() ? $chosenPackage : null;
@endphp

<x-layouts::auth :title="__('Register')" wide>
    <div class="flex flex-col gap-6">
        @if ($selectedPackage)
            <x-auth-header :title="__('Register to buy the :name package', ['name' => $selectedPackage->name])" :description="__('Create your account, then you will go straight to payment.')" />

            <flux:callout icon="archive-box" color="green">
                <flux:callout.heading>{{ __(':name package', ['name' => $selectedPackage->name]) }} &middot; ₹{{ number_format((float) $selectedPackage->price, 2) }}</flux:callout.heading>
                <flux:callout.text>
                    {{ trans_choice(':count product|:count products', $selectedPackage->products->count()) }},
                    {{ trans_choice(':count unit in total|:count units in total', $selectedPackage->unitCount()) }}.
                    <flux:link :href="route('packages.index')" wire:navigate>{{ __('Choose a different package') }}</flux:link>
                </flux:callout.text>
            </flux:callout>
        @else
            <x-auth-header :title="__('Become a Pradiksha franchise holder')" :description="__('Register to view product pricing and place orders. An admin will review and approve your account.')" />

            @if ($chosenPackage)
                <flux:callout icon="no-symbol" color="amber">
                    <flux:callout.heading>{{ __('The :name package is out of stock right now', ['name' => $chosenPackage->name]) }}</flux:callout.heading>
                    <flux:callout.text>{{ __('You can still register, and we will approve your account shortly.') }}</flux:callout.text>
                </flux:callout>
            @endif
        @endif

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            @if ($selectedPackage)
                <input type="hidden" name="package" value="{{ $selectedPackage->slug }}">
            @endif

            <flux:input
                name="name"
                :label="__('Full name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            <flux:input
                name="business_name"
                :label="__('Franchise name')"
                :value="old('business_name')"
                type="text"
                required
                autocomplete="organization"
                :placeholder="__('Name of your franchise / shop')"
            />

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />

                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />
            </div>

            <flux:separator :text="__('Business details')" />

            <flux:input
                name="phone"
                :label="__('Phone number')"
                :value="old('phone')"
                type="tel"
                required
                autocomplete="tel"
            />

            <flux:input
                name="address"
                :label="__('Address')"
                :value="old('address')"
                type="text"
                required
                autocomplete="street-address"
            />

            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-3">
                <flux:input name="city" :label="__('City')" :value="old('city')" type="text" required autocomplete="address-level2" />
                <flux:input name="state" :label="__('State')" :value="old('state')" type="text" required autocomplete="address-level1" />
                <flux:input name="pincode" :label="__('Pincode')" :value="old('pincode')" type="text" required autocomplete="postal-code" />
            </div>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="register-button">
                    {{ __('Register') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('login'))
            <flux:text class="text-center">
                {{ __('Already have an account?') }}
                <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
            </flux:text>
        @endif
    </div>
</x-layouts::auth>
