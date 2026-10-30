@props(['package', 'columns' => false])

@if ($package->inclusionList())
    <ul {{ $attributes->class(['gap-x-6 gap-y-2', 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' => $columns, 'space-y-2' => ! $columns]) }} data-package-inclusions="{{ $package->slug }}">
        @foreach ($package->inclusionList() as $item)
            <li class="flex items-start gap-2 text-sm font-medium">
                <flux:icon.check-circle variant="mini" class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-300" />
                <span>{{ $item }}</span>
            </li>
        @endforeach
    </ul>
@endif
