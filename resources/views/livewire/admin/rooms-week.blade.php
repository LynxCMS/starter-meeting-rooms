{{-- The Bookings screen's grid: one office's rooms against a working week. --}}
<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="office" aria-label="Office" class="blat-select w-auto">
                @foreach ($offices as $choice)
                    <option value="{{ $choice->id }}">{{ $choice->title }}</option>
                @endforeach
            </select>
            <div class="inline-flex overflow-hidden rounded-md border">
                <button type="button" wire:click="previousWeek" class="px-3 py-1.5 text-sm hover:bg-accent" aria-label="Previous week">←</button>
                <button type="button" wire:click="thisWeek" class="border-x px-3 py-1.5 text-sm hover:bg-accent">This week</button>
                <button type="button" wire:click="nextWeek" class="px-3 py-1.5 text-sm hover:bg-accent" aria-label="Next week">→</button>
            </div>
        </div>
        <p class="text-sm text-muted-foreground">
            Week of {{ $days[0]->format('j F Y') }} · times in {{ $officePage?->time_zone ?? $zone }}
        </p>
    </div>

    @if ($notice)
        <p class="rounded-md border bg-muted px-3 py-2 text-sm" role="status">{{ $notice }}</p>
    @endif

    @if ($form)
        @include('livewire.admin.partials.booking-form', ['rooms' => $rooms])
    @endif

    @if ($rooms === [])
        <p class="text-sm text-muted-foreground">This office has no rooms yet. Add them under Rooms in the page tree.</p>
    @else
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[56rem] table-fixed border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="w-40 px-3 py-2 text-left font-medium">Room</th>
                        @foreach ($days as $day)
                            <th @class(['px-3 py-2 text-left font-medium', 'text-primary' => $day->toDateString() === $today])>
                                {{ $day->format('D j M') }}@if ($day->toDateString() === $today) · today @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr class="border-b align-top last:border-b-0">
                            <th scope="row" class="px-3 py-3 text-left font-medium">
                                <a href="{{ route('lynx.admin.pages.edit', [$room->id, 'tab' => 'bookings']) }}" class="inline-flex items-center gap-2 hover:underline">
                                    <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $room->color ?: 'currentColor' }}"></span>
                                    {{ $room->title }}
                                </a>
                                <span class="mt-0.5 block text-xs font-normal text-muted-foreground">{{ $room->capacity }} seats</span>
                            </th>
                            @foreach ($days as $day)
                                @php($date = $day->toDateString())
                                <td class="group border-l px-2 py-2">
                                    <div class="grid grid-cols-1 gap-1.5">
                                        @foreach ($grid[(int) $room->id][$date] ?? [] as $booking)
                                            @include('livewire.admin.partials.booking-chip', ['booking' => $booking])
                                        @endforeach
                                        <div class="flex gap-1 opacity-60 transition-opacity group-hover:opacity-100">
                                            <button type="button" wire:click="startBooking({{ $room->id }}, '{{ $date }}')" class="rounded px-1.5 py-0.5 text-xs hover:bg-accent">+ Book</button>
                                            <button type="button" wire:click="startBlocking({{ $room->id }}, '{{ $date }}')" class="rounded px-1.5 py-0.5 text-xs text-muted-foreground hover:bg-accent">Block</button>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
