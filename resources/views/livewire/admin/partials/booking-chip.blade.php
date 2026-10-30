{{-- One booking or blocked time in a day: its hours and title; opens the form to change or cancel it. --}}
<button type="button" wire:click="editBooking({{ $booking->id }}, '{{ $booking->date }}')"
        @class([
            'w-full rounded-md border px-2 py-1.5 text-left text-xs leading-snug transition-colors hover:border-ring',
            'border-dashed bg-muted text-muted-foreground' => $booking->isBlocked,
            'bg-card' => ! $booking->isBlocked,
        ])>
    <span class="block font-medium tabular-nums">{{ $booking->from }}–{{ $booking->to }}@if ($booking->repeats) <span class="text-muted-foreground" title="Repeats {{ $booking->repeats }}">↻</span>@endif</span>
    <span class="block truncate" title="{{ $booking->title }}">
        @if ($booking->isBlocked)
            Blocked: {{ $booking->title }}
        @else
            {{ $booking->title }}@if ($booking->isPrivate) <span class="text-muted-foreground">(private)</span>@endif
        @endif
    </span>
    @if (! $booking->isBlocked && $booking->organizerId !== null)
        <span class="block truncate text-muted-foreground">{{ $organizers[(string) $booking->organizerId] ?? 'Someone' }}</span>
    @endif
</button>
