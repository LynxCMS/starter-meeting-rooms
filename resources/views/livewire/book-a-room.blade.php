{{-- The staff's booking page: filters, then the rooms that fit with their free start times. --}}
<div class="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-[18rem_1fr]">
    <form class="grid content-start gap-5 rounded-[2rem] bg-birch p-6" wire:submit.prevent>
        <label class="grid gap-1.5">
            <span class="eyebrow text-copper">{{ __('staff.office') }}</span>
            <select wire:model.live="office" class="rounded-full border border-ink/15 bg-paper px-4 py-2.5">
                @foreach ($offices as $choice)
                    <option value="{{ $choice->id }}">{{ $choice->city }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5">
            <span class="eyebrow text-copper">{{ __('staff.day') }}</span>
            <input type="date" wire:model.live="date" min="{{ $today }}" class="rounded-full border border-ink/15 bg-paper px-4 py-2.5">
        </label>
        <label class="grid gap-1.5">
            <span class="eyebrow text-copper">{{ __('staff.length') }}</span>
            <select wire:model.live="minutes" class="rounded-full border border-ink/15 bg-paper px-4 py-2.5">
                @foreach (\App\Livewire\BookARoom::LENGTHS as $length)
                    <option value="{{ $length }}">{{ trans_choice('staff.minutes', $length, ['count' => $length], lynx()->languages->current()->code) }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5">
            <span class="eyebrow text-copper">{{ __('staff.seats') }}</span>
            <input type="number" min="1" max="40" wire:model.live.debounce.400ms="seats" class="rounded-full border border-ink/15 bg-paper px-4 py-2.5">
        </label>
        <fieldset class="grid gap-2">
            <legend class="eyebrow text-copper">{{ __('staff.equipment') }}</legend>
            @foreach (\App\Rooms\FreeRooms::EQUIPMENT as $item)
                <label class="mt-1 flex items-center gap-2">
                    <input type="checkbox" value="{{ $item }}" wire:model.live="equipment" class="size-4 accent-[var(--color-copper)]">
                    {{ __("staff.equipment_{$item}") }}
                </label>
            @endforeach
        </fieldset>
    </form>

    <div class="grid content-start gap-6">
        @if ($booked)
            <p class="rounded-[1.5rem] bg-reed-pale px-6 py-4" role="status">
                {{ __('staff.booked', ['room' => $booked['room'], 'day' => \Carbon\CarbonImmutable::parse($booked['date'])->locale(app()->getLocale())->isoFormat('dddd LL'), 'from' => $booked['from'], 'to' => $booked['to']]) }}
                <a href="{{ route(lynx()->languages->current()->code === 'fr' ? 'staff.mine.fr' : 'staff.mine') }}" class="link-wave ml-1 font-medium">{{ __('staff.my_bookings') }}</a>
            </p>
        @endif

        @forelse ($rooms as ['page' => $room, 'starts' => $slots])
            <section class="wave-rule pt-6" wire:key="room-{{ $room->id }}">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 class="flex items-center gap-3 font-display text-2xl">
                        <span class="size-3 rounded-full" style="background: {{ $room->color ?: 'var(--color-lake)' }}"></span>{{ $room->title }}
                    </h2>
                    <p class="text-sm text-muted">{{ trans_choice('site.rooms.seats', (int) $room->capacity, ['count' => $room->capacity], lynx()->languages->current()->code) }} · {{ $room->floor }}</p>
                </div>

                @if ($slots === [])
                    <p class="mt-3 text-muted">{{ __('staff.no_slots') }}</p>
                @else
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($slots as $slot)
                            <button type="button" wire:click="pick({{ $room->id }}, '{{ $slot }}')"
                                    @class(['rounded-full px-3.5 py-1.5 text-sm tabular-nums transition-colors', 'bg-ink text-paper' => $picked === ['room' => (int) $room->id, 'from' => $slot], 'bg-mist hover:bg-shallows/50' => $picked !== ['room' => (int) $room->id, 'from' => $slot]])>{{ $slot }}</button>
                        @endforeach
                    </div>
                @endif

                @if ($picked !== null && $picked['room'] === (int) $room->id)
                    <form wire:submit="book" class="mt-5 grid gap-3 rounded-[1.5rem] bg-birch p-5 sm:grid-cols-[1fr_auto] sm:items-end">
                        <label class="grid gap-1.5">
                            <span class="text-sm font-medium">{{ __('staff.what', ['from' => $picked['from'], 'to' => \App\Rooms\Slots::plus($picked['from'], $minutes)]) }}</span>
                            <input type="text" wire:model="title" maxlength="120" required autofocus placeholder="{{ __('staff.title_placeholder') }}" class="rounded-full border border-ink/15 bg-paper px-4 py-2.5">
                        </label>
                        <button type="submit" class="rounded-full bg-ink px-6 py-2.5 font-medium text-paper hover:bg-copper">{{ __('staff.book') }}</button>
                        <div class="flex flex-wrap items-center gap-3 text-sm sm:col-span-2">
                            <select wire:model.live="repeat" aria-label="{{ __('staff.repeat') }}" class="rounded-full border border-ink/15 bg-paper px-4 py-2">
                                <option value="">{{ __('staff.repeat_none') }}</option>
                                <option value="weekly">{{ __('staff.repeat_weekly') }}</option>
                                <option value="biweekly">{{ __('staff.repeat_biweekly') }}</option>
                                <option value="monthly">{{ __('staff.repeat_monthly') }}</option>
                            </select>
                            @if ($repeat !== '')
                                <label class="flex items-center gap-2">{{ __('staff.until') }}
                                    <input type="date" wire:model="until" min="{{ $date }}" class="rounded-full border border-ink/15 bg-paper px-4 py-2">
                                </label>
                            @endif
                        </div>
                        <div class="sm:col-span-2">
                            @include('livewire.partials.invite')
                        </div>
                        <label class="flex items-center gap-2 text-sm sm:col-span-2">
                            <input type="checkbox" wire:model="private" class="size-4 accent-[var(--color-copper)]"> {{ __('staff.private') }}
                        </label>
                        @if ($refusal)
                            @include('livewire.partials.refusal', ['page' => $bookingPage, 'message' => $errors->first(), 'span' => 'sm:col-span-2'])
                        @elseif ($errors->any())
                            <p class="text-sm font-medium text-copper sm:col-span-2" role="alert">{{ $errors->first() }}</p>
                        @endif
                    </form>
                @endif
            </section>
        @empty
            <p class="text-lg text-muted">{{ __('staff.no_rooms') }}</p>
        @endforelse
    </div>
</div>
