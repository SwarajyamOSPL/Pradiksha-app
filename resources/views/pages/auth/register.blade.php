<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Become a Pradiksha franchise holder')" :description="__('Register to view product pricing and place orders. An admin will review and approve your account.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

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
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input
                    name="phone"
                    :label="__('Phone number')"
                    :value="old('phone')"
                    type="tel"
                    required
                    autocomplete="tel"
                />

                <flux:input
                    name="business_name"
                    :label="__('Business / shop name')"
                    :value="old('business_name')"
                    type="text"
                    required
                    autocomplete="organization"
                />
            </div>

            <flux:input
                name="address"
                :label="__('Address')"
                :value="old('address')"
                type="text"
                required
                autocomplete="street-address"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
