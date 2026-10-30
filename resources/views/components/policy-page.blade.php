@props([
    'title',
    'intro',
    'highlights' => [],
    'sections' => [],
    'contactText',
    'changesText',
    'updated' => 'October 2026',
])

@php
    $company = config('invoice.company');
@endphp

<x-layouts::storefront :title="$title">
    <div class="mx-auto max-w-3xl px-6 py-12">
        <flux:heading size="xl" level="1" class="text-brand-950 dark:text-cream-50">{{ $title }}</flux:heading>
        <flux:text class="mt-2 text-brand-600 dark:text-brand-300">{{ __('Last updated :date', ['date' => $updated]) }}</flux:text>

        <flux:text class="mt-6 text-brand-700 dark:text-brand-200">{{ $intro }}</flux:text>

        @if (count($highlights) > 0)
            <flux:card class="mt-8 border-brand-100 bg-brand-50 dark:border-brand-800 dark:bg-brand-900/50">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('At a glance') }}</flux:heading>
                <ul class="mt-4 space-y-3">
                    @foreach ($highlights as $point)
                        <li class="flex items-start gap-3 text-brand-800 dark:text-brand-100">
                            <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-300" />
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </flux:card>
        @endif

        <div class="mt-10 space-y-10">
            @foreach ($sections as $number => $section)
                <section id="{{ $section['id'] }}" class="scroll-mt-24">
                    <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ $number + 1 }}. {{ $section['title'] }}</flux:heading>

                    <div class="mt-3 space-y-3">
                        @foreach ($section['body'] as $paragraph)
                            @if (is_array($paragraph))
                                <ul class="list-disc space-y-2 ps-6 text-brand-700 marker:text-brand-500 dark:text-brand-200">
                                    @foreach ($paragraph as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <flux:text class="text-brand-700 dark:text-brand-200">{{ $paragraph }}</flux:text>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section id="contact" class="scroll-mt-24">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ count($sections) + 1 }}. {{ __('How to contact us') }}</flux:heading>

                <div class="mt-3 space-y-3">
                    <flux:text class="text-brand-700 dark:text-brand-200">{{ $contactText }}</flux:text>

                    @if (filled($company['email']) || filled($company['phone']))
                        <ul class="space-y-1 text-brand-800 dark:text-brand-100">
                            @if (filled($company['email']))
                                <li>{{ __('Email') }}: <a href="mailto:{{ $company['email'] }}" class="font-medium text-brand-600 hover:underline dark:text-brand-300">{{ $company['email'] }}</a></li>
                            @endif
                            @if (filled($company['phone']))
                                <li>{{ __('Phone') }}: <a href="tel:{{ $company['phone'] }}" class="font-medium text-brand-600 hover:underline dark:text-brand-300">{{ $company['phone'] }}</a></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </section>

            <section id="changes" class="scroll-mt-24">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ count($sections) + 2 }}. {{ __('Changes to this policy') }}</flux:heading>
                <flux:text class="mt-3 text-brand-700 dark:text-brand-200">{{ $changesText }}</flux:text>
            </section>
        </div>
    </div>
</x-layouts::storefront>
