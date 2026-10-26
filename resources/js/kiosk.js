/*
 * The lobby and door screens (resources/views/kiosk/screen.blade.php). The server sends the day's
 * meetings in the office's time zone; between polls the screen keeps its own clock from the
 * server's minute, so progress bars and "free until" move every second without asking again.
 */
import Alpine from 'alpinejs';

const SECONDS_IN_DAY = 86400;

/** "14:30" as seconds since midnight. */
const seconds = (time) => {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 3600 + minutes * 60;
};

Alpine.data('screen', (config) => ({
    state: config.state,
    words: config.words,
    offline: false,
    busy: false,
    message: '',
    tick: 0,
    receivedAt: performance.now(),

    init() {
        setInterval(() => this.tick++, 1000);
        setInterval(() => this.refresh(), config.poll * 1000);
        this.keepAwake();
        document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.keepAwake());
        this.reloadNightly();
    },

    /** Seconds since midnight in the office, from the server's last answer plus the time since. */
    now() {
        this.tick;
        const elapsed = (performance.now() - this.receivedAt) / 1000;

        return this.state.minute * 60 + this.state.second + elapsed;
    },

    clock() {
        const total = Math.floor(this.now()) % SECONDS_IN_DAY;

        return `${String(Math.floor(total / 3600)).padStart(2, '0')}:${String(Math.floor((total % 3600) / 60)).padStart(2, '0')}`;
    },

    word(key, replace = {}) {
        return Object.entries(replace).reduce((text, [name, value]) => text.replace(`:${name}`, value), this.words[key] ?? key);
    },

    isOpen(room) {
        const now = this.now();

        return room.hours !== null && now >= seconds(room.hours.opens) && now < seconds(room.hours.closes);
    },

    current(room) {
        const now = this.now();

        return room.meetings.find((meeting) => seconds(meeting.from) <= now && now < seconds(meeting.to)) ?? null;
    },

    next(room) {
        const now = this.now();

        return room.meetings.find((meeting) => seconds(meeting.from) > now) ?? null;
    },

    /** free, busy, unavailable (blocked) or closed, for the room's big state. */
    status(room) {
        const meeting = this.current(room);

        if (meeting) {
            return meeting.blocked ? 'unavailable' : 'busy';
        }

        return this.isOpen(room) ? 'free' : 'closed';
    },

    /** "until 15:00", or "Free for the rest of the day". */
    until(room) {
        const meeting = this.current(room);
        const next = this.next(room);

        if (meeting) {
            return this.word('until', { time: meeting.to });
        }

        if (next && room.hours && seconds(next.from) < seconds(room.hours.closes)) {
            return this.word('until', { time: next.from });
        }

        return this.word('free_rest');
    },

    progress(room) {
        const meeting = this.current(room);

        if (!meeting) {
            return 0;
        }

        const start = seconds(meeting.from);

        return Math.min(100, ((this.now() - start) / (seconds(meeting.to) - start)) * 100);
    },

    title(meeting) {
        if (meeting.blocked) {
            return this.word('unavailable');
        }

        return meeting.private ? this.word('busy') : meeting.title;
    },

    isPast(meeting) {
        return seconds(meeting.to) <= this.now();
    },

    /** The lobby shows the firm's logo once every room has closed (or none opens today). */
    afterHours() {
        return this.state.rooms.every((room) => !this.isOpen(room));
    },

    /** The meeting a door screen may check in: about to start or under way, not yet checked in. */
    checkable(room) {
        const now = this.now();

        return room.checkIn
            ? room.meetings.find((meeting) => !meeting.blocked && !meeting.checkedIn && now >= seconds(meeting.from) - config.earlyMinutes * 60 && now < seconds(meeting.to)) ?? null
            : null;
    },

    announcement() {
        const items = this.state.announcements;

        return items.length === 0 ? null : items[Math.floor(this.now() / 12) % items.length];
    },

    async refresh() {
        try {
            const response = await fetch(config.stateUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });

            if (!response.ok) {
                throw new Error(`State answered ${response.status}`);
            }

            this.accept(await response.json());
            this.offline = false;
        } catch {
            // Keep the last state on screen; the next poll tries again.
            this.offline = true;
        }
    },

    async send(url, meeting) {
        this.busy = true;
        this.message = '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking: meeting.id }),
            });
            const answer = await response.json();
            this.accept(response.ok ? answer : answer.state);
            this.message = response.ok ? '' : answer.message;
        } catch {
            this.offline = true;
        } finally {
            this.busy = false;
        }
    },

    accept(state) {
        if (state.date !== this.state.date) {
            location.reload();
        }

        this.state = state;
        this.receivedAt = performance.now();
    },

    async keepAwake() {
        try {
            await navigator.wakeLock?.request('screen');
        } catch {
            // Not every browser allows it (or not without a click); the TV's own settings must then.
        }
    },

    /** Reloads at the configured hour of the screen's own clock, to pick up new code. */
    reloadNightly() {
        const next = new Date();
        next.setHours(config.reloadHour, 0, 0, 0);

        if (next <= new Date()) {
            next.setDate(next.getDate() + 1);
        }

        setTimeout(() => location.reload(), next - new Date());
    },
}));

Alpine.start();
