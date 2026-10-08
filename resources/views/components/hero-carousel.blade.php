@props(['slides', 'interval' => 5000])

<div
    {{ $attributes->class('group relative w-full overflow-hidden bg-brand-50 dark:bg-brand-900') }}
    x-data="{
        active: 0,
        total: {{ count($slides) }},
        timer: null,
        touchStartX: null,
        init() {
            this.start()
        },
        destroy() {
            this.stop()
        },
        start() {
            this.stop()
            if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return
            }
            this.timer = setInterval(() => this.next(), {{ (int) $interval }})
        },
        stop() {
            clearInterval(this.timer)
        },
        next() {
            this.active = (this.active + 1) % this.total
        },
        prev() {
            this.active = (this.active - 1 + this.total) % this.total
        },
        goTo(index) {
            this.active = index
            this.start()
        },
    }"
    x-on:mouseenter="stop()"
    x-on:mouseleave="start()"
    x-on:focusin="stop()"
    x-on:focusout="start()"
    x-on:touchstart.passive="touchStartX = $event.changedTouches[0].clientX"
    x-on:touchend.passive="
        if (touchStartX !== null) {
            const delta = $event.changedTouches[0].clientX - touchStartX
            if (Math.abs(delta) > 40) { delta < 0 ? next() : prev(); start() }
            touchStartX = null
        }
    "
    x-on:keydown.left="prev(); start()"
    x-on:keydown.right="next(); start()"
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ __('Featured product ranges') }}"
>
    <div class="relative aspect-video w-full">
        @foreach ($slides as $index => $slide)
            <div
                class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                :class="active === {{ $index }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
                role="group"
                aria-roledescription="slide"
                aria-label="{{ __('Slide :current of :total', ['current' => $index + 1, 'total' => count($slides)]) }}"
                :aria-hidden="active !== {{ $index }}"
            >
                <img
                    src="{{ $slide['src'] }}"
                    alt="{{ $slide['alt'] }}"
                    width="1672"
                    height="941"
                    class="size-full object-cover"
                    @if ($index > 0) loading="lazy" @endif
                    draggable="false"
                />
            </div>
        @endforeach
    </div>

    @if (count($slides) > 1)
        <button
            type="button"
            x-on:click="prev(); start()"
            class="absolute start-3 top-1/2 flex size-10 sm:start-6 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-brand-800 shadow-md backdrop-blur-sm transition hover:bg-white focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 dark:bg-brand-950/70 dark:text-cream-50 dark:hover:bg-brand-950"
            aria-label="{{ __('Previous slide') }}"
        >
            <flux:icon.chevron-left class="size-5" />
        </button>

        <button
            type="button"
            x-on:click="next(); start()"
            class="absolute end-3 top-1/2 flex size-10 sm:end-6 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-brand-800 shadow-md backdrop-blur-sm transition hover:bg-white focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 dark:bg-brand-950/70 dark:text-cream-50 dark:hover:bg-brand-950"
            aria-label="{{ __('Next slide') }}"
        >
            <flux:icon.chevron-right class="size-5" />
        </button>

        <div class="absolute inset-x-0 bottom-2 flex justify-center sm:bottom-4">
            <div class="flex items-center gap-2 rounded-full bg-white/70 px-3 py-1.5 backdrop-blur-sm dark:bg-brand-950/60">
                @foreach ($slides as $index => $slide)
                    <button
                        type="button"
                        x-on:click="goTo({{ $index }})"
                        class="h-2 rounded-full bg-brand-700/40 transition-all duration-300 hover:bg-brand-700/70"
                        :class="active === {{ $index }} ? 'w-6 bg-brand-700!' : 'w-2'"
                        :aria-current="active === {{ $index }}"
                        aria-label="{{ __('Go to slide :number', ['number' => $index + 1]) }}"
                    ></button>
                @endforeach
            </div>
        </div>
    @endif
</div>
