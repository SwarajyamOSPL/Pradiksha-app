<x-layouts::auth :title="__('Account pending approval')">
    <div class="flex flex-col gap-6 text-center">
        <x-auth-header
            :title="auth()->user()->status->label()"
            :description="auth()->user()->status->value === 'suspended'
                ? __('Your account has been suspended. Please contact Pradiksha Herbal support for assistance.')
                : __('Thanks for registering! An admin is reviewing your account and will approve it shortly. You will be able to see pricing and place orders once approved.')"
        />

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
