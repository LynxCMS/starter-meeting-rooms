{{-- One practice area: what it covers, who handles it, and its latest cases. --}}
@php($posts = lynx()->pages->find("template=post, practice_areas={$page->id}, sort=-published_on, limit=3"))
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" :back="$page->parent()" />
    <div class="mx-auto grid max-w-6xl gap-14 px-5 sm:px-8 md:grid-cols-[1fr_20rem]">
        <div class="copy">@field($page->body)</div>
        <aside class="space-y-10 md:sticky md:top-8 md:self-start">
            <x-portrait :page="$page" class="aspect-[5/4] rounded-[2rem]" />
            @if ($page->lawyers->count())
                <div>
                    <h2 class="eyebrow text-copper">@string('practice.who_leads', ['en' => 'Who handles it', 'fr' => 'Qui s’en occupe'], label: 'Practice area: lawyers heading', scope: 'site')</h2>
                    <ul class="mt-5 space-y-4">
                        @foreach ($page->lawyers as $lawyer)
                            <li><x-lawyer-link :lawyer="$lawyer" /></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
    @if ($posts->count())
        <section class="mx-auto mt-20 max-w-6xl px-5 sm:px-8">
            <h2 class="text-title pb-6">@string('practice.news', ['en' => 'Cases and news', 'fr' => 'Dossiers et nouvelles'], label: 'Practice area: news heading', scope: 'site')</h2>
            @foreach ($posts as $post)
                <x-post-row :post="$post" />
            @endforeach
        </section>
    @endif
</x-layout>
