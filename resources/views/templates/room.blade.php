{{-- One room: its details, its opening hours, and when it is free in the next days it opens. --}}
@php($range = fn (string $from, string $to): string => __('site.rooms.range', ['from' => $from, 'to' => $to]))
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :back="$page->parent()">
        <p class="mt-4 flex flex-wrap items-center gap-3 text-muted">
            <span class="size-3 rounded-full" style="background: {{ $page->color ?: 'var(--color-lake)' }}"></span>
            {{ trans_choice('site.rooms.seats', (int) $page->capacity, locale: lynx()->languages->current()->code) }} · {{ $page->floor }} · {{ $page->office?->city }}@if ($page->equipment->count()) · {{ implode(' · ', $page->equipment->labels()) }}@endif
        </p>
    </x-page-header>

    <div class="mx-auto grid max-w-6xl gap-12 px-5 sm:px-8 md:grid-cols-[20rem_1fr]">
        <section>
            <h2 class="eyebrow text-copper">@string('room.hours', ['en' => 'Opening hours', 'fr' => 'Heures d’ouverture'], label: 'Room: opening hours heading', scope: 'site')</h2>
            <dl class="mt-4 [&>div]:wave-rule">
                @forelse ($page->opening_hours as $hours)
                    <div class="flex justify-between py-2.5"><dt>{{ $hours->weekday }}</dt><dd class="tabular-nums text-muted">{{ $range((string) $hours->opens, (string) $hours->closes) }}</dd></div>
                @empty
                    <div class="py-2.5 text-muted">@string('room.closed', ['en' => 'Not open for bookings.', 'fr' => 'Fermée aux réservations.'], label: 'Room: no opening hours', scope: 'site')</div>
                @endforelse
            </dl>
        </section>

        <section>
            <h2 class="eyebrow text-copper">@string('room.coming', ['en' => 'Coming days', 'fr' => 'Prochains jours'], label: 'Room: coming days heading', scope: 'site')</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($days as $day)
                    <li class="rounded-[1.5rem] bg-birch px-6 py-4">
                        <span class="font-display text-lg">
                            @if ($day['date']->isToday())
                                @string('room.today', ['en' => 'Today', 'fr' => 'Aujourd’hui'], label: 'Room: today', scope: 'site')
                            @else
                                {{ $day['date']->locale(app()->getLocale())->isoFormat('dddd LL') }}
                            @endif
                        </span>
                        @foreach ($day['bookings'] as $booking)
                            <span @class(['mt-1 block text-sm tabular-nums', 'text-copper' => $booking->isBlocked])>
                                {{ $range($booking->from, $booking->to) }} ·
                                @if ($booking->isBlocked)
                                    @string('room.blocked', ['en' => 'Blocked:', 'fr' => 'Bloquée :'], label: 'Room: before a blocked time', scope: 'site') {{ $booking->title }}
                                @elseif ($booking->isPrivate)
                                    @string('room.busy', ['en' => 'Busy', 'fr' => 'Occupée'], label: 'Room: a private meeting', scope: 'site')
                                @else
                                    {{ $booking->title }}
                                @endif
                            </span>
                        @endforeach
                        <span class="mt-1 block text-sm text-reed">
                            @if ($day['free'] === [])
                                @string('room.full', ['en' => 'Fully booked', 'fr' => 'Complet'], label: 'Room: no free time', scope: 'site')
                            @else
                                @string('room.free', ['en' => 'Free:', 'fr' => 'Libre :'], label: 'Room: before its free times', scope: 'site') {{ collect($day['free'])->map(fn ($free) => $range($free['from'], $free['to']))->implode(', ') }}
                            @endif
                        </span>
                    </li>
                @empty
                    <li class="text-muted">@string('room.none', ['en' => 'No opening days in the next two weeks.', 'fr' => 'Aucun jour d’ouverture dans les deux prochaines semaines.'], label: 'Room: no coming days', scope: 'site')</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layout>
