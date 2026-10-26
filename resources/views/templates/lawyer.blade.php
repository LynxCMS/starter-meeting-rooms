{{-- One lawyer: portrait, role and species, what they do, a fun fact, their areas and their office. --}}
@php($areas = lynx()->pages->find("template=practice-area, lawyers={$page->id}"))
<x-layout :page="$page" :title="(string) $page->title">
    <article class="mx-auto grid max-w-6xl gap-12 px-5 pt-10 sm:px-8 sm:pt-16 md:grid-cols-[22rem_1fr] md:gap-16">
        <div class="rise">
            <x-portrait :page="$page" :eager="true" :width="720" class="aspect-[5/4] rounded-[2.5rem]" />
            @if ($page->fun_fact)
                <aside class="mt-8 rotate-[-1.5deg] rounded-3xl bg-copper-pale p-6">
                    <p class="eyebrow text-copper">@string('lawyer.fun_fact', ['en' => 'Fun fact', 'fr' => 'Le saviez-vous?'], label: 'Lawyer: fun fact heading', scope: 'site')</p>
                    <p class="mt-2 font-display text-lg leading-snug">{{ $page->fun_fact }}</p>
                </aside>
            @endif
        </div>
        <div class="rise [animation-delay:120ms]">
            <a href="{{ $page->parent()?->url() }}" class="eyebrow inline-flex items-center gap-2 text-muted hover:text-copper"><span aria-hidden="true">←</span> {{ $page->parent()?->title }}</a>
            <p class="eyebrow mt-8 text-copper"><x-job-title :lawyer="$page" /></p>
            <h1 class="text-title mt-3">{{ $page->title }}</h1>
            <p class="mt-2 font-display text-xl italic text-muted">{{ $page->species }}</p>
            <p class="mt-8 max-w-2xl text-xl leading-relaxed text-pretty">{{ $page->summary }}</p>
            <div class="copy mt-6 text-muted">@field($page->body)</div>

            <dl class="mt-12 grid gap-8 wave-rule pt-8 sm:grid-cols-2">
                @if ($areas->count())
                    <div>
                        <dt class="eyebrow text-ink">@string('lawyer.practises_in', ['en' => 'Practises in', 'fr' => 'Domaines'], label: 'Lawyer: practice areas label', scope: 'site')</dt>
                        <dd class="mt-3 flex flex-wrap gap-2">
                            @foreach ($areas as $area)
                                <a href="{{ $area->url() }}" class="rounded-full bg-mist px-4 py-1.5 text-sm text-lake-deep transition-colors hover:bg-lake hover:text-paper">{{ $area->title }}</a>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if ($page->office)
                    <div>
                        <dt class="eyebrow text-ink">@string('lawyer.based_in', ['en' => 'Based in', 'fr' => 'Bureau'], label: 'Lawyer: office label', scope: 'site')</dt>
                        <dd class="mt-3"><a href="{{ $page->office->url() }}" class="font-display text-xl hover:text-copper">{{ $page->office->city }}</a></dd>
                    </div>
                @endif
            </dl>
        </div>
    </article>
</x-layout>
