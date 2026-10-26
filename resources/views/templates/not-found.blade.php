{{--
    The page not found: Lynx shows it, with status 404, for addresses that match no page. Its title,
    text and drawing are the page's own, so editors write them; the button is a site text.
--}}
@php($home = lynx()->pages->get('/'))
<x-layout :title="(string) $page->title" robots="noindex">
    <section class="mx-auto grid max-w-5xl items-center gap-12 px-5 pb-8 pt-10 sm:px-8 sm:pt-16 md:grid-cols-[1.1fr_1fr]">
        <div class="rise">
            <p class="eyebrow text-copper">404</p>
            <h1 class="text-title mt-3">{{ $page->title }}</h1>
            @if ((string) $page->summary !== '')
                <p class="mt-4 max-w-md text-lg text-muted">{{ $page->summary }}</p>
            @endif
            @if ($home)
                <a href="{{ $home->url() }}" class="mt-8 inline-block rounded-full bg-ink px-6 py-3.5 font-medium text-paper transition-colors hover:bg-copper">@string('not_found.home', ['en' => 'Back to the home page', 'fr' => 'Retour à l’accueil'], label: 'Page not found: button', scope: 'site')</a>
            @endif
        </div>
        <x-illustration :name="$page->illustration?->value ?: 'muskrat'" :eager="true" class="rise aspect-[5/4] w-full rounded-[2.5rem] object-cover [animation-delay:120ms]" />
    </section>
</x-layout>
