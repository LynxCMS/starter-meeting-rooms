{{-- One office: where it is, how to call it, the time its rooms keep, and who works there. --}}
@php($people = lynx()->pages->find("template=lawyer, office={$page->id}"))
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->city" :summary="$page->summary" :back="$page->parent()" />
    <div class="mx-auto grid max-w-6xl gap-14 px-5 sm:px-8 md:grid-cols-[20rem_1fr]">
        <dl class="space-y-7">
            <div>
                <dt class="eyebrow text-copper">@string('office.address', ['en' => 'Address', 'fr' => 'Adresse'], label: 'Office: address label', scope: 'site')</dt>
                <dd class="mt-2"><address class="whitespace-pre-line not-italic leading-relaxed">{{ $page->address }}</address></dd>
            </div>
            <div>
                <dt class="eyebrow text-copper">@string('office.phone', ['en' => 'Phone', 'fr' => 'Téléphone'], label: 'Office: phone label', scope: 'site')</dt>
                <dd class="mt-2"><a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $page->phone) }}" class="font-display text-2xl hover:text-copper">{{ $page->phone }}</a></dd>
            </div>
            <p class="text-sm text-muted">{{ __('site.local_time', ['zone' => (string) $page->time_zone]) }}</p>
        </dl>
        @if ($people->count())
            <section>
                <h2 class="eyebrow wave-rule pt-5 text-ink">@string('office.people', ['en' => 'Who works here', 'fr' => 'Qui travaille ici'], label: 'Office: people heading', scope: 'site')</h2>
                <ul class="mt-6 grid gap-6 sm:grid-cols-2">
                    @foreach ($people as $lawyer)
                        <li><x-lawyer-link :lawyer="$lawyer" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layout>
