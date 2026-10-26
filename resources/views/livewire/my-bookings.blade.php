{{-- "My bookings": the person's meetings that have not ended, by day, to move, rename or cancel. --}}
<div class="mx-auto max-w-4xl px-5 sm:px-8">
    @if ($notice)
        <p class="mb-6 rounded-[1.5rem] bg-reed-pale px-6 py-4" role="status">{{ $notice }}</p>
    @endif

    @forelse ($days as $date => $bookings)
        <section class="wave-rule pb-4 pt-6">
            <h2 class="eyebrow text-copper">{{ \Carbon\CarbonImmutable::parse($date)->locale(app()->getLocale())->isoFormat('dddd LL') }}</h2>
            <ul class="mt-4 grid gap-3">
                @foreach ($bookings as ['booking' => $booking, 'room' => $room])
                    <li class="rounded-[1.5rem] bg-birch p-5" wire:key="booking-{{ $booking->id }}-{{ $booking->date }}">
                        @if ($editing !== null && $editing['booking'] === $booking->id && $editing['occurrence'] === $booking->date)
                            <form wire:submit="save" class="grid gap-3 sm:grid-cols-4">
                                @if ($editing['repeats'] !== null)
                                    <fieldset class="flex flex-wrap gap-x-5 gap-y-1 text-sm sm:col-span-4">
                                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="editing.scope" value="one" class="accent-[var(--color-copper)]"> {{ __('staff.this_one') }}</label>
                                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="editing.scope" value="series" class="accent-[var(--color-copper)]"> {{ __('staff.whole_series') }}</label>
                                        @if ($editing['scope'] === 'series')
                                            <span class="w-full text-muted">{{ __('staff.series_note') }}</span>
                                        @endif
                                    </fieldset>
                                @endif
                                <label class="grid gap-1 text-sm sm:col-span-2">{{ __('staff.day') }}
                                    <input type="date" wire:model="editing.date" @disabled($editing['scope'] === 'series') class="rounded-full border border-ink/15 bg-paper px-4 py-2 disabled:opacity-50">
                                </label>
                                <label class="grid gap-1 text-sm">{{ __('staff.from') }}
                                    <input type="time" step="300" wire:model="editing.from" class="rounded-full border border-ink/15 bg-paper px-4 py-2 tabular-nums">
                                </label>
                                <label class="grid gap-1 text-sm">{{ __('staff.to') }}
                                    <input type="time" step="300" wire:model="editing.to" class="rounded-full border border-ink/15 bg-paper px-4 py-2 tabular-nums">
                                </label>
                                <label class="grid gap-1 text-sm sm:col-span-3">{{ __('staff.title') }}
                                    <input type="text" wire:model="editing.title" maxlength="120" class="rounded-full border border-ink/15 bg-paper px-4 py-2">
                                </label>
                                <label class="flex items-center gap-2 self-end pb-2 text-sm">
                                    <input type="checkbox" wire:model="editing.private" class="size-4 accent-[var(--color-copper)]"> {{ __('staff.private_short') }}
                                </label>
                                @if (\App\Livewire\MyBookings::mayInvite($editing))
                                    <div class="sm:col-span-4">@include('livewire.partials.invite')</div>
                                @endif
                                @if ($errors->any())
                                    <p class="text-sm font-medium text-copper sm:col-span-4" role="alert">{{ $errors->first() }}</p>
                                @endif
                                <div class="flex flex-wrap gap-3 sm:col-span-4">
                                    <button type="submit" class="rounded-full bg-ink px-5 py-2 text-sm font-medium text-paper hover:bg-copper">{{ __('staff.save') }}</button>
                                    <button type="button" wire:click="$set('editing', null)" class="rounded-full px-4 py-2 text-sm hover:bg-paper">{{ __('staff.close') }}</button>
                                </div>
                            </form>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="font-display text-xl">{{ $booking->title }}@if ($booking->isPrivate) <span class="text-sm text-muted">({{ __('staff.private_short') }})</span>@endif</p>
                                    @if ($booking->repeats)
                                        <p class="text-sm text-copper">↻ {{ __('staff.repeats.'.$booking->repeats) }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-muted">
                                        <span class="tabular-nums">{{ $booking->from }}–{{ $booking->to }}</span> · {{ $room?->title }}
                                        @if ($booking->checkedInAt) · {{ __('screen.checked_in') }}@endif
                                    </p>
                                    @if ($booking->attendees !== [])
                                        <p class="mt-1 text-sm text-muted">{{ __('staff.with', ['people' => implode(', ', array_filter(array_map(fn (int $id) => $names[$id] ?? null, $booking->attendees)))]) }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="edit({{ $booking->id }}, '{{ $booking->date }}')" class="rounded-full border border-ink/15 px-4 py-1.5 text-sm hover:bg-paper">{{ __('staff.change') }}</button>
                                    @if ($booking->repeats)
                                        <button type="button" wire:click="cancel({{ $booking->id }}, '{{ $booking->date }}')" wire:confirm="{{ __('staff.cancel_confirm') }}" class="rounded-full border border-copper/40 px-4 py-1.5 text-sm text-copper hover:bg-copper-pale">{{ __('staff.cancel_one') }}</button>
                                        <button type="button" wire:click="cancel({{ $booking->id }}, '{{ $booking->date }}', 'series')" wire:confirm="{{ __('staff.cancel_series_confirm') }}" class="rounded-full border border-copper/40 px-4 py-1.5 text-sm text-copper hover:bg-copper-pale">{{ __('staff.cancel_series') }}</button>
                                    @else
                                        <button type="button" wire:click="cancel({{ $booking->id }}, '{{ $booking->date }}')" wire:confirm="{{ __('staff.cancel_confirm') }}" class="rounded-full border border-copper/40 px-4 py-1.5 text-sm text-copper hover:bg-copper-pale">{{ __('staff.cancel') }}</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-lg text-muted">{{ __('staff.none') }}</p>
    @endforelse

    {{-- The person's own calendar feed: their meetings, as organizer or invited, in their calendar app. --}}
    <section class="wave-rule mt-10 pt-8" x-data="{ copied: false }">
        <h2 class="font-display text-2xl">{{ __('staff.feed_title') }}</h2>
        <p class="mt-2 max-w-2xl text-muted">{{ __('staff.feed_intro') }}</p>
        @if ($feedUrl)
            <div class="mt-4 grid gap-3 rounded-[1.5rem] bg-reed-pale p-5">
                <p class="text-sm font-medium">{{ __('staff.feed_once') }}</p>
                <code class="block break-all rounded-xl bg-paper px-4 py-2 text-sm">{{ $feedUrl }}</code>
                <button type="button" x-on:click="navigator.clipboard.writeText(@js($feedUrl)); copied = true" class="w-fit rounded-full bg-ink px-5 py-2 text-sm font-medium text-paper hover:bg-copper">
                    <span x-show="! copied">{{ __('staff.feed_copy') }}</span><span x-show="copied" x-cloak>{{ __('staff.feed_copied') }}</span>
                </button>
            </div>
        @elseif ($feed)
            <p class="mt-3 text-sm">{{ __('staff.feed_live', ['date' => $feed->created_at->locale(app()->getLocale())->isoFormat('LL')]) }}</p>
        @endif
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" wire:click="issueFeed" @if ($feed) wire:confirm="{{ __('staff.feed_replace_confirm') }}" @endif class="rounded-full border border-ink/15 px-5 py-2 text-sm hover:bg-birch">
                {{ $feed ? __('staff.feed_replace') : __('staff.feed_issue') }}
            </button>
            @if ($feed)
                <button type="button" wire:click="revokeFeed" wire:confirm="{{ __('staff.feed_revoke_confirm') }}" class="rounded-full border border-copper/40 px-5 py-2 text-sm text-copper hover:bg-copper-pale">{{ __('staff.feed_revoke') }}</button>
            @endif
        </div>
    </section>
</div>
