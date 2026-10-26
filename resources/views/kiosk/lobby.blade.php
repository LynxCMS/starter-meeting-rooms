{{-- A lobby screen: the office's rooms, now and next, with announcements; the logo after hours. --}}
<div class="flex h-full flex-col">
    <header class="flex items-center justify-between px-14 pb-6 pt-10">
        <div>
            <p class="font-display text-4xl">Castor <em class="text-copper-pale">&amp;</em> Otters</p>
            <p class="mt-1 text-2xl text-paper/60" x-text="state.office"></p>
        </div>
        <p class="font-display text-7xl tabular-nums" x-text="clock()"></p>
    </header>

    <main class="flex-1 px-14" x-show="! afterHours()">
        <div class="grid h-full auto-rows-fr gap-6" :class="state.rooms.length > 2 ? 'grid-cols-2' : 'grid-cols-1'">
            <template x-for="room in state.rooms" :key="room.id">
                <section class="flex flex-col justify-between rounded-[2.5rem] bg-paper/[0.06] p-10 ring-1 ring-paper/10">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <span class="size-5 rounded-full" :style="`background: ${room.color || 'var(--color-shallows)'}`"></span>
                            <h2 class="font-display text-5xl" x-text="room.name"></h2>
                        </div>
                        <span class="rounded-full px-6 py-2 text-2xl font-semibold"
                              :class="{ 'bg-reed': status(room) === 'free', 'bg-copper': status(room) === 'busy', 'bg-paper/20': status(room) === 'unavailable' || status(room) === 'closed' }"
                              x-text="status(room) === 'closed' ? @js(__('screen.closed', [], $language)) : word(status(room))"></span>
                    </div>

                    <div class="mt-6" x-show="status(room) !== 'closed'">
                        <p class="text-3xl" x-text="current(room) ? title(current(room)) + ' · ' + until(room) : until(room)"></p>
                        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-paper/15" x-show="current(room)">
                            <div class="h-full rounded-full bg-paper transition-[width] duration-1000 ease-linear" :style="`width: ${progress(room)}%`"></div>
                        </div>
                    </div>

                    <p class="mt-6 text-2xl text-paper/60" x-show="next(room)">
                        <span>{{ __('screen.next', [], $language) }}</span>
                        <span class="tabular-nums" x-text="next(room) ? ' ' + next(room).from + '–' + next(room).to + ' · ' + title(next(room)) : ''"></span>
                    </p>
                </section>
            </template>
        </div>
    </main>

    <main class="grid flex-1 place-items-center" x-show="afterHours()" x-cloak>
        @include('kiosk.brand', ['class' => 'h-64'])
    </main>

    <footer class="mx-14 mb-10 mt-6 min-h-28 rounded-[2rem] bg-[var(--screen-accent,var(--color-copper))] px-10 py-6" x-show="announcement()" x-cloak>
        <template x-if="announcement()">
            <div>
                <p class="font-display text-3xl" x-text="announcement().title"></p>
                <p class="mt-1 text-2xl text-paper/85" x-text="announcement().body"></p>
            </div>
        </template>
    </footer>
</div>
