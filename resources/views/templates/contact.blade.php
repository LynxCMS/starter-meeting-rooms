{{-- The contact page: how to reach the firm, the consultation request, and both offices. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <div class="mx-auto grid max-w-6xl gap-14 px-5 sm:px-8 md:grid-cols-[1.25fr_1fr]">
        <div>
            <div class="copy">@field($page->body)</div>
            <section class="mt-10 rounded-[2rem] bg-birch p-6 sm:p-10" aria-labelledby="consultation">
                <h2 id="consultation" class="font-display text-3xl">@string('consultation.title', ['en' => 'Request a consultation', 'fr' => 'Demander une consultation'], label: 'Consultation form title')</h2>
                <div class="mt-6">
                    <livewire:lynx-form form="consultation" :page-id="$page->id" />
                </div>
            </section>
        </div>
        <div>
            <h2 class="eyebrow text-copper">@string('contact_offices.eyebrow', ['en' => 'Write, call, or swim by', 'fr' => 'Écrivez, appelez ou venez à la nage'], label: 'Contact Offices Eyebrow')</h2>
            @foreach (lynx()->pages->find('template=office, sort=sort') as $office)
                <div class="mt-6 grid gap-2 wave-rule pt-6 sm:grid-cols-[1fr_auto]">
                    <div>
                        <a href="{{ $office->url() }}" class="font-display text-2xl hover:text-copper">{{ $office->city }}</a>
                        <address class="mt-2 whitespace-pre-line not-italic leading-relaxed text-muted">{{ $office->address }}</address>
                    </div>
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $office->phone) }}" class="self-start rounded-full bg-ink px-5 py-2.5 text-sm font-medium text-paper hover:bg-copper">
                        @string('contact_offices.call', ['en' => 'Call', 'fr' => 'Appeler'], label: 'Call button, before the number') {{ $office->phone }}
                    </a>
                </div>
            @endforeach
            <x-illustration name="reeds" class="mt-10 aspect-[5/4] w-full rounded-[2rem] object-cover" />
        </div>
    </div>
</x-layout>
