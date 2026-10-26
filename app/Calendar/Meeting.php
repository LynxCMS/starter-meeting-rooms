<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Bookings\Booking;
use App\Bookings\Series;
use LynxCms\Core\Support\Value;
use Zap\Models\Schedule;

/**
 * A booking as calendars see it: one event, with a stable key, whatever Zap made of it. A series
 * is split over several schedules (Series), so it is read whole: its pattern from the first date
 * to the last, each date either a meeting of the series, one changed on its own (an override), or
 * taken out (excluded). Invites, feeds and mail are built from this.
 */
final readonly class Meeting
{
    /**
     * @param  list<Occurrence>  $occurrences  every date that has a meeting, in date order
     * @param  list<string>  $excluded  dates of the pattern with no meeting
     */
    public function __construct(
        public string $key,
        public Booking $booking,
        public Occurrence $first,
        public ?Recurrence $recurrence,
        public array $occurrences,
        public array $excluded = [],
    ) {}

    /** The meeting a schedule belongs to: the schedule alone, or its whole series. */
    public static function of(Schedule $schedule): self
    {
        $booking = Booking::fromSchedule($schedule);

        if ($booking->series === null) {
            $occurrence = Occurrence::of($booking);

            return new self('booking-'.$booking->id, $booking, $occurrence, null, [$occurrence]);
        }

        return self::series($booking->series, Series::members($schedule));
    }

    /** The meeting of a series, or null when no date of it is left. */
    public static function ofSeries(string $series): ?self
    {
        $schedule = Schedule::query()->with('periods')->where('metadata->series', $series)->first();

        return $schedule instanceof Schedule ? self::of($schedule) : null;
    }

    public function isSeries(): bool
    {
        return $this->recurrence !== null;
    }

    /**
     * The organizer and the people invited, as user ids.
     *
     * @return list<int>
     */
    public function people(): array
    {
        return $this->booking->people();
    }

    /** @return list<Occurrence> */
    public function overrides(): array
    {
        return array_values(array_filter($this->occurrences, fn (Occurrence $occurrence): bool => $occurrence->isOverride));
    }

    public function on(string $date): ?Occurrence
    {
        foreach ($this->occurrences as $occurrence) {
            if ($occurrence->date === $date) {
                return $occurrence;
            }
        }

        return null;
    }

    /**
     * @param  list<Schedule>  $members
     */
    private static function series(string $series, array $members): self
    {
        $recurring = array_values(array_filter($members, Series::isRecurring(...)));
        $oneOffs = array_values(array_filter($members, fn (Schedule $member): bool => ! Series::isRecurring($member)));
        $master = $recurring[0] ?? $oneOffs[0];
        $booking = Booking::fromSchedule($master);
        $recurrence = self::recurrence($master, $recurring, $oneOffs);

        $overrides = [];

        foreach ($oneOffs as $oneOff) {
            $overrides[self::occurrenceOf($oneOff)] = Occurrence::of(Booking::fromSchedule($oneOff), self::occurrenceOf($oneOff), isOverride: true);
        }

        [$occurrences, $excluded] = [[], []];

        foreach ($recurrence->dates() as $date) {
            $member = self::covering($recurring, $date);
            $occurrence = $member === null ? ($overrides[$date] ?? null) : Occurrence::of(Booking::fromSchedule($member, $date));
            unset($overrides[$date]);

            if ($occurrence === null) {
                $excluded[] = $date;
            } else {
                $occurrences[] = $occurrence;
            }
        }

        $occurrences = [...$occurrences, ...array_values($overrides)];
        usort($occurrences, fn (Occurrence $a, Occurrence $b): int => [$a->date, $a->from] <=> [$b->date, $b->from]);
        $first = new Occurrence($booking->id, $recurrence->first, $recurrence->first, $booking->from, $booking->to, $booking->roomId, $booking->title);

        return new self('series-'.$series, $booking, $first, $recurrence, $occurrences, $excluded);
    }

    /**
     * The series' pattern as booked (metadata repeat, first, until), or as its schedules show it
     * for series booked before those were kept.
     *
     * @param  list<Schedule>  $recurring
     * @param  list<Schedule>  $oneOffs
     */
    private static function recurrence(Schedule $master, array $recurring, array $oneOffs): Recurrence
    {
        $metadata = Value::map($master->metadata);
        $startOf = fn (Schedule $member): string => Series::isRecurring($member) ? $member->start_date->toDateString() : self::occurrenceOf($member);
        $starts = [$startOf($master), ...array_map($startOf, [...$recurring, ...$oneOffs])];
        $ends = array_map(fn (Schedule $member): string => $member->end_date?->toDateString() ?? $member->start_date->toDateString(), $recurring);

        return new Recurrence(
            repeat: Value::string($metadata['repeat'] ?? null, Series::pattern($master) ?? 'weekly'),
            first: Value::string($metadata['first'] ?? null, min($starts)),
            until: Value::string($metadata['until'] ?? null, $ends === [] ? max($starts) : max($ends)),
        );
    }

    /** @param  list<Schedule>  $recurring */
    private static function covering(array $recurring, string $date): ?Schedule
    {
        foreach ($recurring as $member) {
            $end = $member->end_date?->toDateString();

            if ($member->start_date->toDateString() <= $date && ($end === null || $date <= $end)) {
                return $member;
            }
        }

        return null;
    }

    /** The date of the pattern a one-off stands for: kept when it was taken out, else its own. */
    private static function occurrenceOf(Schedule $oneOff): string
    {
        return Value::string(Value::map($oneOff->metadata)['occurrence'] ?? null, $oneOff->start_date->toDateString());
    }
}
