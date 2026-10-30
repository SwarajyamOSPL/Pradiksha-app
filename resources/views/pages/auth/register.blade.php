{{-- Only reachable with a package that can be bought: the Fortify register view sends everyone else to the packages page. --}}
<x-layouts::auth :title="__('Register')" wide>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Register to buy the :name package', ['name' => $selectedPackage->name])" :description="__('Create your account, then you will go straight to payment.')" />

        <flux:callout icon="archive-box" color="green">
            <flux:callout.heading>{{ __(':name package', ['name' => $selectedPackage->name]) }} &middot; ₹{{ number_format((float) $selectedPackage->price, 2) }}</flux:callout.heading>
            <flux:callout.text>
                {{ trans_choice(':count product|:count products', $selectedPackage->products->count()) }},
                {{ trans_choice(':count unit in total|:count units in total', $selectedPackage->unitCount()) }}.
                <flux:link :href="route('packages.index')" wire:navigate>{{ __('Choose a different package') }}</flux:link>
            </flux:callout.text>
        </flux:callout>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="package" value="{{ $selectedPackage->slug }}">

            @error('package')
                <flux:callout icon="exclamation-triangle" color="red">
                    <flux:callout.heading>{{ $message }}</flux:callout.heading>
                </flux:callout>
            @enderror

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

            <flux:text size="sm" class="text-center">
                {{ __('By registering you agree to our') }}
                <flux:link :href="route('policy.privacy')" target="_blank">{{ __('Privacy Policy') }}</flux:link>
                {{ __('and') }}
                <flux:link :href="route('policy.refund')" target="_blank">{{ __('Refund & Return Policy') }}</flux:link>.
            </flux:text>

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
