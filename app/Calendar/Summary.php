<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * What a meeting's mail says about it, read when the change is made, so a queued mail tells what
 * was decided, not what the meeting has become since. For a change or cancellation of one date of
 * a series, $on says which date, and where and when it now is (no time: that date is cancelled).
 * A series is shown by its pattern and its next date.
 */
final readonly class Summary
{
    /** @param  list<string>  $attendees */
    public function __construct(
        public string $title,
        public bool $isPrivate,
        public string $location,
        public string $room,
        public string $city,
        public string $date,
        public string $from,
        public string $to,
        public ?string $repeat,
        public ?string $until,
        public string $organizer,
        public array $attendees,
        public ?string $on = null,
        public ?string $onFrom = null,
        public ?string $onTo = null,
        public ?string $onRoom = null,
    ) {}

    /** The meeting as a whole; with $date, also that one date of it (null there: it is gone). */
    public static function of(Meeting $meeting, ?string $date = null): self
    {
        $shown = $meeting->isSeries() ? self::next($meeting) : ($meeting->occurrences[0] ?? $meeting->first);
        $place = $shown->place();
        $changed = $date === null ? null : $meeting->on($date);
        $people = User::query()->whereIn('id', $meeting->people())->orderBy('name')->get();
        $organizer = $people->first(fn (User $person): bool => (string) $person->id === (string) $meeting->booking->organizerId);

        return new self(
            title: $meeting->booking->title,
            isPrivate: $meeting->booking->isPrivate,
            location: $place->location(),
            room: $place->room,
            city: $place->city,
            date: $shown->date,
            from: $shown->from,
            to: $shown->to,
            repeat: $meeting->recurrence?->repeat,
            until: $meeting->recurrence?->until,
            organizer: $organizer instanceof User ? $organizer->name : '',
            attendees: array_values($people->reject(fn (User $person): bool => $person->is($organizer))->map(fn (User $person): string => $person->name)->all()),
            on: $date,
            onFrom: $changed?->from,
            onTo: $changed?->to,
            onRoom: $changed?->place()->room,
        );
    }

    /** A series' next date that keeps its pattern's time and room (its first, once all are past). */
    private static function next(Meeting $meeting): Occurrence
    {
        $today = CarbonImmutable::today($meeting->first->place()->zone)->toDateString();

        foreach ($meeting->occurrences as $occurrence) {
            if (! $occurrence->isOverride && $occurrence->date >= $today) {
                return $occurrence;
            }
        }

        return $meeting->first;
    }
}
