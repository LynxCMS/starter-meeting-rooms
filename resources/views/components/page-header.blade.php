@props(['title', 'eyebrow' => null, 'summary' => null, 'back' => null])
{{-- The top of an inner page: where it sits, its title and its one-line introduction. --}}
<header {{ $attributes->merge(['class' => 'rise mx-auto max-w-6xl px-5 pb-12 pt-10 sm:px-8 sm:pt-16']) }}>
    @if ($back)
        <a href="{{ $back->url() }}" class="eyebrow inline-flex items-center gap-2 text-muted transition-colors hover:text-copper">
            <span aria-hidden="true">←</span> {{ $back->title }}
        </a>
    @elseif ($eyebrow)
        <p class="eyebrow text-copper">{{ $eyebrow }}</p>
    @endif
    <h1 class="text-title mt-4 max-w-4xl text-balance">{{ $title }}</h1>
    @if ((string) $summary !== '')
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted text-pretty">{{ $summary }}</p>
    @endif
    {{ $slot }}
</header>
