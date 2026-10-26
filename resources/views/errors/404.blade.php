{{-- A page that isn't there: the muskrat searched every channel and came back with the home page. --}}
@php($home = lynx()->pages->get('/'))
<x-layout robots="noindex">
    <x-slot:title>@string('not_found.title', ['en' => 'This page swam off', 'fr' => 'Cette page a filé à la nage'], label: 'Page not found: title', scope: 'site')</x-slot:title>
    <section class="mx-auto grid max-w-5xl items-center gap-12 px-5 pb-8 pt-10 sm:px-8 sm:pt-16 md:grid-cols-[1.1fr_1fr]">
        <div class="rise">
            <p class="eyebrow text-copper">404</p>
            <h1 class="text-title mt-3">@string('not_found.title', ['en' => 'This page swam off', 'fr' => 'Cette page a filé à la nage'], label: 'Page not found: title', scope: 'site')</h1>
            <p class="mt-4 max-w-md text-lg text-muted">@string('not_found.text', ['en' => 'Our muskrat searched every channel and couldn’t find it. It may have moved, or never existed.', 'fr' => 'Notre rat musqué a fouillé chaque chenal sans la trouver. Elle a peut-être déménagé, ou n’a jamais existé.'], label: 'Page not found: text', scope: 'site')</p>
            @if ($home)
                <a href="{{ $home->url() }}" class="mt-8 inline-block rounded-full bg-ink px-6 py-3.5 font-medium text-paper transition-colors hover:bg-copper">@string('not_found.home', ['en' => 'Back to the home page', 'fr' => 'Retour à l’accueil'], label: 'Page not found: button', scope: 'site')</a>
            @endif
        </div>
        <x-illustration name="muskrat" :eager="true" class="rise aspect-[5/4] w-full rounded-[2.5rem] object-cover [animation-delay:120ms]" />
    </section>
</x-layout>
