{{-- Every room, by office, with its seats, floor and equipment. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title">
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted text-pretty">@string('rooms.note', ['en' => 'For the firm’s staff: rooms and their bookings.', 'fr' => 'Pour le personnel : les salles et leurs réservations.'], label: 'Introduction')</p>
    </x-page-header>
    <div class="mx-auto max-w-6xl space-y-14 px-5 sm:px-8">
        @foreach (lynx()->pages->find('template=office, sort=sort') as $office)
            @php($rooms = $page->children("template=room, office={$office->id}, sort=title"))
            @if ($rooms->count())
                <section>
                    <h2 class="eyebrow wave-rule pt-5 text-copper">{{ $office->city }}</h2>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        @foreach ($rooms as $room)
                            <a href="{{ $room->url() }}" class="group flex gap-5 rounded-[1.75rem] bg-birch p-6 transition-colors hover:bg-mist">
                                <span class="mt-1.5 size-4 shrink-0 rounded-full" style="background: {{ $room->color ?: 'var(--color-lake)' }}"></span>
                                <span>
                                    <span class="block font-display text-2xl group-hover:text-copper">{{ $room->title }}</span>
                                    <span class="mt-1 block text-sm text-muted">{{ trans_choice('site.rooms.seats', (int) $room->capacity) }} · {{ $room->floor }}</span>
                                    @if ($room->equipment->count())
                                        <span class="mt-3 block text-sm">{{ implode(' · ', $room->equipment->labels()) }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</x-layout>
