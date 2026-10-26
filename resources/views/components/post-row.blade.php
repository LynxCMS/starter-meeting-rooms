@props(['post'])
{{-- A case or a news item in a docket: its date and kind in the margin, then its title and summary. --}}
<article {{ $attributes->merge(['class' => 'group relative grid gap-2 wave-rule py-7 sm:grid-cols-[11rem_1fr] sm:gap-8']) }}>
    <p class="text-sm text-muted">
        <time datetime="{{ $post->published_on?->toDateString() }}">{{ $post->published_on?->locale(app()->getLocale())->isoFormat('LL') }}</time>
        <span @class(['eyebrow mt-1 block', 'text-copper' => $post->category?->value === 'case', 'text-reed' => $post->category?->value !== 'case'])>{{ $post->category }}</span>
    </p>
    <div>
        <h3 class="font-display text-2xl leading-snug text-balance">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0 group-hover:text-copper">{{ $post->title }}</a>
        </h3>
        <p class="mt-2 max-w-2xl leading-relaxed text-muted">{{ $post->summary }}</p>
    </div>
</article>
