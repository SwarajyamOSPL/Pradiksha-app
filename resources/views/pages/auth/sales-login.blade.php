<x-layouts::auth :title="__('Sales team login')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Sales team login')" :description="__('Log in with the account your admin created for you.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="portal" value="sales">

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="sales-login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        <flux:text class="text-center">
            {{ __('Franchise holder?') }}
            <flux:link :href="route('login')" wire:navigate>{{ __('Go to the franchise login') }}</flux:link>
        </flux:text>
    </div>
</x-layouts::auth>
