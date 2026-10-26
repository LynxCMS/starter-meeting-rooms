{{-- A case or a news item: its kind and date, the story, and the practice areas it belongs to. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" :back="$page->parent()">
        <p class="mt-6 flex flex-wrap items-center gap-3 text-sm text-muted">
            <span @class(['eyebrow rounded-full px-3 py-1', 'bg-copper-pale text-copper' => $page->category?->value === 'case', 'bg-reed-pale text-reed' => $page->category?->value !== 'case'])>{{ $page->category }}</span>
            <time datetime="{{ $page->published_on?->toDateString() }}">{{ $page->published_on?->locale(app()->getLocale())->isoFormat('LL') }}</time>
        </p>
    </x-page-header>
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        <div class="copy">@field($page->body)</div>
        @if ($page->practice_areas->count())
            <div class="mt-12 flex flex-wrap items-center gap-2 wave-rule pt-6">
                <span class="eyebrow mr-2">@string('post.related', ['en' => 'Related practice areas', 'fr' => 'Domaines liés'], label: 'Post: related areas label', scope: 'site')</span>
                @foreach ($page->practice_areas as $area)
                    <a href="{{ $area->url() }}" class="rounded-full bg-mist px-4 py-1.5 text-sm text-lake-deep transition-colors hover:bg-lake hover:text-paper">{{ $area->title }}</a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
