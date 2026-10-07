@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand
        :name="config('app.name')"
        :logo="asset('images/logo.webp')"
        :alt="config('app.name')"
        {{ $attributes }}
    />
@else
    <flux:brand :name="config('app.name')" {{ $attributes }}>
        <x-slot:logo class="h-10 min-w-10">
            <img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" class="h-full w-auto object-contain" />
        </x-slot:logo>
    </flux:brand>
@endif
