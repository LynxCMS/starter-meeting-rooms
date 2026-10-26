{{-- A room's Calendar feed tab: its feed's state, the address right after issuing, and revoking. --}}
<div class="grid max-w-2xl gap-5">
    <div class="rounded-xl border bg-card p-5">
        <h2 class="text-base font-semibold">Calendar feed</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Outlook, Google Calendar and Apple Calendar can subscribe to this secret address and show {{ $room->title }}'s
            meetings and blocked times, updated every few minutes. Private meetings show only as “Busy”.
            Anyone with the address sees the room's calendar, so share it with care, and revoke it if it travels too far.
        </p>

        <p class="mt-4 text-sm">
            @if ($feed)
                <span class="font-medium">Published</span> since {{ $feed->created_at->format('j M Y, H:i') }}.
                {{ $feed->last_seen_at ? 'Last read '.$feed->last_seen_at->diffForHumans().'.' : 'Not read yet.' }}
            @else
                <span class="font-medium">No feed.</span> Issue one to subscribe to the room's calendar.
            @endif
        </p>

        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" wire:click="issue"
                    @if ($feed) wire:confirm="Issue a new address? Calendars subscribed to the current one stop updating." @endif
                    class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                {{ $feed ? 'Issue a new address' : 'Issue address' }}
            </button>
            @if ($feed)
                <button type="button" wire:click="revoke" wire:confirm="Revoke the address? Calendars subscribed to it stop updating."
                        class="inline-flex h-9 items-center rounded-md border border-destructive/40 px-4 text-sm font-medium text-destructive hover:bg-destructive/10">
                    Revoke
                </button>
            @endif
        </div>
    </div>

    @if ($issued)
        <div class="grid gap-3 rounded-xl border border-primary/40 bg-card p-5" x-data="{ copied: false }">
            <p class="text-sm font-medium">Shown only now. Add it to a calendar app as a subscription (“From URL” or “Subscribe to calendar”).</p>
            <code class="block break-all rounded-md bg-muted px-3 py-2 text-xs">{{ $issued }}</code>
            <button type="button" x-on:click="navigator.clipboard.writeText(@js($issued)); copied = true"
                    class="inline-flex h-8 w-fit items-center rounded-md border px-3 text-sm hover:bg-accent">
                <span x-show="! copied">Copy address</span><span x-show="copied" x-cloak>Copied</span>
            </button>
        </div>
    @endif
</div>
