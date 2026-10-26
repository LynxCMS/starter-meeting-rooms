{{-- A door screen: the room's big state, the buttons to check in or end the meeting, and its day. --}}
<div class="grid h-full grid-cols-[3fr_2fr]" x-data="{ get room() { return state.rooms[0] ?? null } }">
    <template x-if="room === null">
        <div class="col-span-2 grid place-items-center text-3xl">{{ $state['title'] }}</div>
    </template>

    <template x-if="room !== null">
        <section class="relative flex flex-col justify-between p-12 transition-colors duration-700"
                 :class="{ 'bg-reed': status(room) === 'free', 'bg-copper': status(room) === 'busy', 'bg-ink': status(room) === 'unavailable', 'bg-lake-deep': status(room) === 'closed' }">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h1 class="font-display text-6xl leading-none" x-text="room.name"></h1>
                    <p class="mt-3 text-2xl text-paper/75" x-text="word('seats', { count: room.capacity }) + (room.floor ? ' · ' + room.floor : '')"></p>
                </div>
                <p class="font-display text-5xl tabular-nums" x-text="clock()"></p>
            </div>

            <div x-show="status(room) !== 'closed'">
                <p class="font-display text-[9rem] leading-none" x-text="word(status(room))"></p>
                <p class="mt-4 text-4xl" x-text="until(room)"></p>
                <template x-if="current(room)">
                    <div class="mt-8">
                        <p class="text-3xl" x-text="title(current(room))"></p>
                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-paper/25">
                            <div class="h-full rounded-full bg-paper transition-[width] duration-1000 ease-linear" :style="`width: ${progress(room)}%`"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="status(room) === 'closed'" class="grid place-items-center gap-8">
                @include('kiosk.brand', ['class' => 'h-48'])
                <p class="text-4xl">{{ __('screen.closed', [], $language) }}</p>
            </div>

            <div class="flex min-h-24 flex-wrap items-end gap-4">
                <template x-if="checkable(room)">
                    <button type="button" x-on:click="send(@js(route('kiosk.check-in', $token)), checkable(room))" :disabled="busy"
                            class="rounded-full bg-paper px-10 py-5 text-3xl font-semibold text-ink disabled:opacity-60">{{ __('screen.check_in', [], $language) }}</button>
                </template>
                <template x-if="current(room) && ! current(room).blocked">
                    <button type="button" x-on:click="send(@js(route('kiosk.end-early', $token)), current(room))" :disabled="busy"
                            class="rounded-full border-2 border-paper/70 px-8 py-4 text-2xl disabled:opacity-60">{{ __('screen.end_now', [], $language) }}</button>
                </template>
                <p x-show="checkable(room)" class="max-w-xl text-xl text-paper/80">{{ __('screen.check_in_hint', ['minutes' => config('rooms.check_in_minutes')], $language) }}</p>
                <p x-show="message" x-text="message" class="w-full text-xl"></p>
            </div>
        </section>
    </template>

    <aside class="flex flex-col overflow-hidden bg-ink p-10" x-show="room !== null">
        <h2 class="eyebrow text-xl text-shallows">{{ __('screen.today', [], $language) }}</h2>
        <template x-if="room && room.hours">
            <p class="mt-2 text-xl text-paper/60" x-text="word('open_from', { from: room.hours.opens, to: room.hours.closes })"></p>
        </template>
        <ol class="mt-8 grid content-start gap-4">
            <template x-for="meeting in (room ? room.meetings : [])" :key="meeting.id">
                <li class="rounded-3xl px-6 py-5 transition-opacity"
                    :class="{ 'opacity-40': isPast(meeting), 'bg-paper/10 ring-2 ring-paper/40': current(room) && current(room).id === meeting.id, 'bg-paper/5': ! (current(room) && current(room).id === meeting.id) }">
                    <p class="text-2xl tabular-nums" x-text="meeting.from + '–' + meeting.to"></p>
                    <p class="mt-1 truncate text-2xl font-medium" x-text="title(meeting)"></p>
                    <p x-show="meeting.checkedIn" class="mt-1 text-lg text-shallows">{{ __('screen.checked_in', [], $language) }}</p>
                </li>
            </template>
        </ol>
        <p x-show="room && room.meetings.length === 0" class="mt-8 text-2xl text-paper/70">{{ __('screen.nothing_today', [], $language) }}</p>
    </aside>
</div>
