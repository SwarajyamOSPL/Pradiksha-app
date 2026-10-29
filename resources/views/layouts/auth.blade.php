@props(['title' => null, 'wide' => false])

<x-layouts::auth.simple :title="$title" :wide="$wide">
    {{ $slot }}
</x-layouts::auth.simple>
