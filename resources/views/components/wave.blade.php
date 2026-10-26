@props(['flip' => false, 'flowing' => false])
{{--
    A wavy edge where two colours meet. Its fill is the colour on the flat side: put it at the top of a
    section with the colour above it, or flipped at the bottom with the colour below.

    Flowing waves drift sideways forever: the path is drawn twice as wide as the view (2400 units) and
    repeats every 300, so sliding it by 1200 loops without a seam. A fainter wave behind drifts the
    other way, like shallows. Both stand still for visitors who asked for less motion.
--}}
@php
    // The edge from the right to the left: down to $depth, then a crest or trough every 150 units.
    $segments = fn (int $width, int $depth = 18): string => 'M0 0H'.$width.'V'.$depth.'q-75 16 -150 0'.str_repeat('t-150 0', intdiv($width, 150) - 1).'z';
@endphp
<svg viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true" focusable="false"
     {{ $attributes->merge(['class' => 'block h-6 w-full overflow-hidden sm:h-10'.($flip ? ' -scale-y-100' : '')]) }}>
    @if ($flowing)
        <path class="wave-drift-back opacity-40" d="{{ $segments(2400, 22) }}"/>
        <path class="wave-drift" d="{{ $segments(2400) }}"/>
    @else
        <path d="{{ $segments(1200) }}"/>
    @endif
</svg>
