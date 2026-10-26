{{-- The home page: the firm in a sentence, what it does, who founded it, its latest cases and its offices. --}}
@php
    $areas = lynx()->pages->get('/practice-areas/');
    $team = lynx()->pages->get('/team/');
    $news = lynx()->pages->get('/news/');
    $contact = lynx()->pages->get('/contact/');
    $founders = lynx()->pages->find('template=lawyer, role=founding-partner');
    $latest = lynx()->pages->find('template=post, sort=-published_on, limit=3');
    $offices = lynx()->pages->find('template=office, sort=sort');
@endphp
<x-layout :page="$page">
    <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-12 pt-8 sm:px-8 md:grid-cols-[1.15fr_1fr] md:pt-16">
        <div class="rise">
            <p class="eyebrow text-copper">Vancouver · Montréal</p>
            <h1 class="text-display mt-5 text-balance">{{ $page->summary }}</h1>
            @if ($page->tagline)
                <p class="mt-5 font-display text-2xl italic text-copper sm:text-3xl">@field($page->tagline)</p>
            @endif
            <div class="copy mt-6 text-muted">@field($page->body)</div>
            <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-4">
                @if ($contact)
                    <a href="{{ $contact->url() }}" class="rounded-full bg-ink px-6 py-3.5 font-medium text-paper transition-colors hover:bg-copper">@string('hero.consultation', ['en' => 'Request a consultation', 'fr' => 'Demander une consultation'], label: 'Hero: consultation button')</a>
                @endif
                @if ($team)
                    <a href="{{ $team->url() }}" class="link-wave font-medium transition-colors hover:text-copper">@string('hero.team', ['en' => 'Meet the team', 'fr' => 'Rencontrer l’équipe'], label: 'Hero: team link')</a>
                @endif
            </div>
        </div>
        {{-- The founders: Castor, and an Otter sibling pinned over the corner like a photo on a case board. --}}
        <div class="rise relative mx-auto w-full max-w-lg pb-10 [animation-delay:150ms]">
            <x-illustration name="beaver" :eager="true" class="aspect-[5/4] w-full rounded-[2.5rem] object-cover" />
            <x-illustration name="otter" :eager="true" class="absolute -bottom-2 -left-8 aspect-[5/4] w-1/2 rotate-[-6deg] rounded-[1.5rem] object-cover shadow-[0_20px_40px_-20px_oklch(30%_0.06_225/0.45)] ring-8 ring-paper" />
        </div>
    </section>

    @if ($page->highlights?->count())
        {{-- The firm in figures, each with a straight face. --}}
        <dl class="rise mx-auto grid max-w-6xl gap-8 px-5 pb-20 [animation-delay:250ms] sm:grid-cols-3 sm:px-8">
            @foreach ($page->highlights as $highlight)
                <div class="wave-rule pt-6">
                    <dt class="font-display text-4xl text-ink">{{ $highlight->figure }}</dt>
                    <dd class="mt-2 max-w-xs leading-relaxed text-muted">{{ $highlight->figure_note }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($areas)
        <section class="bg-birch">
            <x-wave :flowing="true" class="-translate-y-px fill-paper" />
            <div class="mx-auto max-w-6xl px-5 py-16 sm:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 class="text-title">@string('areas.title', ['en' => 'What we do', 'fr' => 'Ce que nous faisons'], label: 'Practice areas: heading')</h2>
                    <a href="{{ $areas->url() }}" class="link-wave font-medium text-lake transition-colors hover:text-copper">@string('areas.all', ['en' => 'Every practice area', 'fr' => 'Tous les domaines'], label: 'Practice areas: link')</a>
                </div>
                <ol class="mt-10">
                    @foreach ($areas->children() as $area)
                        <li class="group relative grid items-baseline gap-2 wave-rule py-6 sm:grid-cols-[4rem_1fr_1.2fr] sm:gap-8">
                            <span class="font-display text-2xl text-copper">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="font-display text-2xl leading-snug"><a href="{{ $area->url() }}" class="after:absolute after:inset-0 group-hover:text-copper">{{ $area->title }}</a></h3>
                            <p class="leading-relaxed text-muted">{{ $area->summary }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
            <x-wave :flip="true" :flowing="true" class="translate-y-px fill-paper" />
        </section>
    @endif

    @if ($team && $founders->count())
        <section class="mx-auto max-w-6xl px-5 py-24 sm:px-8">
            <h2 class="text-title max-w-2xl text-balance">{{ $team->summary }}</h2>
            <div class="mt-14 grid gap-12 sm:grid-cols-3">
                @foreach ($founders as $founder)
                    <a href="{{ $founder->url() }}" @class(['group block', 'sm:translate-y-10' => $loop->iteration === 2])>
                        <x-portrait :page="$founder" class="aspect-[5/4] rounded-[2rem] transition-transform duration-700 ease-out-quint group-hover:-translate-y-1" />
                        <p class="eyebrow mt-5 text-copper"><x-job-title :lawyer="$founder" /> · {{ $founder->office?->city }}</p>
                        <h3 class="mt-2 font-display text-2xl group-hover:text-copper">{{ $founder->title }}</h3>
                        <p class="mt-2 leading-relaxed text-muted">{{ $founder->summary }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($news && $latest->count())
        <section class="mx-auto max-w-6xl px-5 py-16 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4 pb-6">
                <h2 class="text-title">@string('news.title', ['en' => 'Cases and news', 'fr' => 'Dossiers et nouvelles'], label: 'News: heading')</h2>
                <a href="{{ $news->url() }}" class="link-wave font-medium text-lake transition-colors hover:text-copper">@string('news.all', ['en' => 'All cases and news', 'fr' => 'Tous les dossiers et nouvelles'], label: 'News: link')</a>
            </div>
            @foreach ($latest as $post)
                <x-post-row :post="$post" />
            @endforeach
        </section>
    @endif

    <section class="mx-auto grid max-w-6xl gap-6 px-5 pt-10 sm:px-8 md:grid-cols-2">
        @foreach ($offices as $office)
            <x-office-card :office="$office" :illustration="$loop->first ? 'kingfisher' : 'loon'" />
        @endforeach
    </section>
</x-layout>
