{{-- A screen's Kiosk link tab: its link's state, the link and QR code right after issuing, and revoking. --}}
<div class="grid max-w-2xl gap-5">
    <div class="rounded-xl border bg-card p-5">
        <h2 class="text-base font-semibold">Kiosk link</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            The screen opens this secret address in its browser and shows the
            {{ $screen->kind?->value === 'door' ? 'room\'s day' : 'office\'s rooms' }} without anyone signing in.
            Anyone with the address sees what the screen shows, so keep it on the screen, and revoke it if a screen is lost.
        </p>

        <p class="mt-4 text-sm">
            @if ($link)
                <span class="font-medium">Linked</span> since {{ $link->created_at->format('j M Y, H:i') }}.
                {{ $link->last_seen_at ? 'Last opened '.$link->last_seen_at->diffForHumans().'.' : 'Not opened yet.' }}
            @else
                <span class="font-medium">No link.</span> The screen shows nothing until one is issued.
            @endif
        </p>

        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" wire:click="issue"
                    @if ($link) wire:confirm="Issue a new link? The screen's current address stops working at once." @endif
                    class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                {{ $link ? 'Issue a new link' : 'Issue link' }}
            </button>
            @if ($link)
                <button type="button" wire:click="revoke" wire:confirm="Revoke the link? The screen stops showing bookings."
                        class="inline-flex h-9 items-center rounded-md border border-destructive/40 px-4 text-sm font-medium text-destructive hover:bg-destructive/10">
                    Revoke
                </button>
            @endif
        </div>
    </div>

    @if ($issued)
        <div class="grid gap-4 rounded-xl border border-primary/40 bg-card p-5 sm:grid-cols-[auto_1fr]" x-data="{ copied: false }">
            <div class="rounded-lg bg-white p-2 [&_svg]:block">{!! $qr !!}</div>
            <div class="grid content-start gap-3">
                <p class="text-sm font-medium">Shown only now. Open it on the screen, or scan the code with it.</p>
                <code class="block break-all rounded-md bg-muted px-3 py-2 text-xs">{{ $issued }}</code>
                <button type="button" x-on:click="navigator.clipboard.writeText(@js($issued)); copied = true"
                        class="inline-flex h-8 w-fit items-center rounded-md border px-3 text-sm hover:bg-accent">
                    <span x-show="! copied">Copy address</span><span x-show="copied" x-cloak>Copied</span>
                </button>
            </div>
        </div>
    @endif
</div>
