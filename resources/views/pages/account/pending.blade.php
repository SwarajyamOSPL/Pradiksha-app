<x-layouts::auth :title="__('Account pending approval')">
    <div class="flex flex-col gap-6 text-center">
        <x-auth-header
            :title="auth()->user()->status->label()"
            :description="auth()->user()->status->value === 'suspended'
                ? __('Your account has been suspended. Please contact Pradiksha Herbal support for assistance.')
                : __('Thanks for registering! Your request has been sent to our admin for approval. You will be able to log in, see pricing and place orders once it is approved.')"
        />

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
