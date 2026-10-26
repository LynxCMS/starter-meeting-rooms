{{-- Every practice area, with its drawing, its line and who handles it. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        @foreach ($page->children() as $area)
            <article class="group relative grid items-center gap-8 wave-rule py-10 md:grid-cols-[14rem_1fr_16rem]">
                <x-portrait :page="$area" class="aspect-[5/4] w-48 rounded-[2rem] transition-transform duration-700 ease-out-quint group-hover:-rotate-2 md:w-full" />
                <div>
                    <p class="font-display text-xl text-copper">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                    <h2 class="mt-2 font-display text-3xl leading-tight text-balance">
                        <a href="{{ $area->url() }}" class="after:absolute after:inset-0 group-hover:text-copper">{{ $area->title }}</a>
                    </h2>
                    <p class="mt-3 max-w-xl text-lg leading-relaxed text-muted">{{ $area->summary }}</p>
                </div>
                <p class="text-sm leading-relaxed text-muted">
                    <span class="eyebrow block text-ink">@string('areas.who_leads', ['en' => 'Who handles it', 'fr' => 'Qui s’en occupe'], label: 'Lawyers label on each area')</span>
                    {{ $area->lawyers->implode(', ') }}
                </p>
            </article>
        @endforeach
    </div>
</x-layout>
