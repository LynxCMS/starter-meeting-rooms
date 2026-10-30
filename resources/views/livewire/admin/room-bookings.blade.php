{{-- A room's Bookings tab: its working week, day by day. --}}
<div class="grid gap-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="inline-flex overflow-hidden rounded-md border">
            <button type="button" wire:click="previousWeek" class="px-3 py-1.5 text-sm hover:bg-accent" aria-label="Previous week">←</button>
            <button type="button" wire:click="thisWeek" class="border-x px-3 py-1.5 text-sm hover:bg-accent">This week</button>
            <button type="button" wire:click="nextWeek" class="px-3 py-1.5 text-sm hover:bg-accent" aria-label="Next week">→</button>
        </div>
        <p class="text-sm text-muted-foreground">
            Week of {{ $days[0]->format('j F Y') }} · times in {{ $room->office?->time_zone ?? $zone }}
            · <a href="{{ route('lynx.admin.bookings') }}" class="underline hover:text-foreground">every room</a>
        </p>
    </div>

    @if ($notice)
        <p class="rounded-md border bg-muted px-3 py-2 text-sm" role="status">{{ $notice }}</p>
    @endif

    @if ($form)
        @include('livewire.admin.partials.booking-form', ['rooms' => [$room]])
    @endif

    <div class="grid gap-3 sm:grid-cols-5">
        @foreach ($days as $day)
            @php($date = $day->toDateString())
            <section class="grid grid-cols-1 content-start gap-2 rounded-xl border p-3">
                <h3 @class(['text-sm font-medium', 'text-primary' => $date === $today])>
                    {{ $day->format('D j M') }}@if ($date === $today) · today @endif
                </h3>
                @forelse ($grid[(int) $room->id][$date] ?? [] as $booking)
                    @include('livewire.admin.partials.booking-chip', ['booking' => $booking])
                @empty
                    <p class="text-xs text-muted-foreground">Free all day.</p>
                @endforelse
                <div class="flex gap-1">
                    <button type="button" wire:click="startBooking({{ $room->id }}, '{{ $date }}')" class="rounded px-1.5 py-0.5 text-xs hover:bg-accent">+ Book</button>
                    <button type="button" wire:click="startBlocking({{ $room->id }}, '{{ $date }}')" class="rounded px-1.5 py-0.5 text-xs text-muted-foreground hover:bg-accent">Block</button>
                </div>
            </section>
        @endforeach
    </div>
</div>
