{{--
    Search: the words in ?q, looked up in Lynx's search index (titles, summaries, texts, species and
    fun facts) in the visitor's language. Pages a visitor may not see, such as the staff's rooms,
    never show. Results are never stored by the page cache (SiteFeaturesSeeder).
--}}
@php
    $query = trim(Str::limit((string) request()->query('q', ''), 100, ''));
    // Quotes would end the selector's value; words are what the index matches anyway.
    $words = trim(preg_replace('/["\\\\]+/', ' ', $query) ?? '');
    $results = $words === '' ? null : lynx()->pages->find('search*="'.$words.'", limit=30');
@endphp
<x-layout :page="$page" :title="(string) $page->title" robots="noindex, follow">
    <x-page-header :title="$page->title">
        <form method="GET" action="{{ $page->url() }}" role="search" class="mt-8 flex max-w-2xl flex-col gap-3 sm:flex-row">
            <label for="q" class="sr-only">{{ __('site.search.label') }}</label>
            <input id="q" type="search" name="q" value="{{ $query }}" placeholder="@string('search.placeholder', ['en' => 'Beavers, dams, migration, Montréal…', 'fr' => 'Castors, barrages, migration, Vancouver…'], label: 'Search box placeholder')" autocomplete="off" @if ($query === '') autofocus @endif
                   class="min-w-0 flex-1 rounded-full border border-ink/15 bg-paper px-5 py-3.5 text-lg outline-none transition-colors focus:border-copper">
            <button type="submit" class="rounded-full bg-ink px-7 py-3.5 font-medium text-paper transition-colors hover:bg-copper">@string('search.go', ['en' => 'Search', 'fr' => 'Chercher'], label: 'Search button')</button>
        </form>
    </x-page-header>

    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        @if ($results !== null)
            <p class="text-muted" role="status">{{ trans_choice('site.search.found', $results->count(), ['count' => $results->count(), 'query' => $query]) }}</p>
            <ul class="mt-6">
                @foreach ($results as $result)
                    <li class="group relative wave-rule py-6">
                        <p class="eyebrow text-copper">{{ $result->parent()?->title }}</p>
                        <h2 class="mt-1 font-display text-2xl"><a href="{{ $result->url() }}" class="after:absolute after:inset-0 group-hover:text-copper">{{ $result->title }}</a></h2>
                        @if ($result->summary)
                            <p class="mt-2 max-w-2xl leading-relaxed text-muted">{{ $result->summary }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($results->isEmpty())
                <div class="mt-4 grid items-center gap-8 sm:grid-cols-[1fr_18rem]">
                    <p class="max-w-md text-lg">@string('search.none', ['en' => 'Our heron has been staring at the water for a while now. Try other words, or ask us directly.', 'fr' => 'Notre héron fixe l’eau depuis un moment déjà. Essayez d’autres mots, ou écrivez-nous directement.'], label: 'No results text')</p>
                    <x-illustration name="heron" class="aspect-[5/4] w-full rounded-[2rem] object-cover" />
                </div>
            @endif
        @endif
    </div>
</x-layout>
