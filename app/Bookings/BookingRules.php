<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Room;
use App\Rooms\OpeningHours;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Models\Schedule;

/**
 * The office's rules for a booking, checked before Zap saves it: in the room's opening hours
 * (Zap's free slots follow them, but it saves appointments outside them), between the shortest
 * and longest meeting, and not in the past (in the room's office's time zone). Zap then refuses overlaps, buffer included, and this
 * turns its refusal into a message that names times, never other people's titles.
 */
final class BookingRules
{
    /**
     * $date may be past for a whole series, whose rules apply to its weekday, not one date.
     *
     * @throws BookingRefused
     */
    public static function check(Room $room, string $date, string $from, string $to, bool $isBlock = false, bool $allowPast = false): void
    {
        $minutes = self::minutes($from, $to);
        $min = Value::int(config('rooms.min_minutes'), 15);
        $max = Value::int(config('rooms.max_minutes'), 240);

        match (true) {
            $minutes <= 0 => throw BookingRefused::because('to', 'backwards', [], 'A meeting must end after it starts.'),
            ! $isBlock && $minutes < $min => throw BookingRefused::because('to', 'too_short', ['minutes' => $min], "A meeting lasts at least {$min} minutes."),
            ! $isBlock && $minutes > $max => throw BookingRefused::because('to', 'too_long', ['minutes' => $max], 'A meeting lasts at most '.self::duration($max).'.'),
            ! $allowPast && CarbonImmutable::parse("{$date} {$from}", $room->timeZone())->isPast() => throw BookingRefused::because('date', 'past', [], 'That time has already passed.'),
            default => self::checkOpen($room, $date, $from, $to),
        };
    }

    /**
     * Zap's conflict, as a message about the room: "Atlas is taken from 10:00 to 11:00."
     */
    public static function conflict(Room $room, ScheduleConflictException $exception): BookingRefused
    {
        $periods = [];

        foreach ($exception->getConflictingSchedules() as $schedule) {
            if ($schedule instanceof Schedule) {
                $booking = Booking::fromSchedule($schedule->loadMissing('periods'));
                $periods[] = [$booking->from, $booking->to];
            }
        }

        $periods = array_values(array_unique($periods, SORT_REGULAR));
        $name = self::name($room);
        $taken = $periods === [] ? "{$name} is taken then." : "{$name} is taken ".implode(' and ', array_map(fn (array $period): string => "from {$period[0]} to {$period[1]}", $periods)).'.';
        $buffer = Value::int(config('rooms.buffer_minutes'), 0);
        $values = ['room' => $name, 'times' => implode(', ', array_map(fn (array $period): string => "{$period[0]}–{$period[1]}", $periods)), 'buffer' => $buffer];

        return BookingRefused::because('from', 'taken', $values, $buffer > 0 ? "{$taken} It needs {$buffer} minutes between meetings." : $taken);
    }

    private static function checkOpen(Room $room, string $date, string $from, string $to): void
    {
        $page = $room->page();
        $weekday = strtolower(CarbonImmutable::parse($date)->format('l'));
        $hours = array_values(array_filter($page instanceof Page ? OpeningHours::of($page) : [], fn (array $group): bool => in_array($weekday, $group['days'], true)));
        $plural = ucfirst($weekday).'s';

        if ($hours === []) {
            throw BookingRefused::because('date', 'closed', ['room' => self::name($room), 'date' => $date], self::name($room)." is closed on {$plural}.");
        }

        if (! array_any($hours, fn (array $group): bool => $from >= $group['opens'] && $to <= $group['closes'])) {
            $open = implode(' and ', array_map(fn (array $group): string => "from {$group['opens']} to {$group['closes']}", $hours));
            $values = ['room' => self::name($room), 'date' => $date, 'hours' => implode(', ', array_map(fn (array $group): string => "{$group['opens']}–{$group['closes']}", $hours))];

            throw BookingRefused::because('from', 'closed_then', $values, self::name($room)." is open {$open} on {$plural}.");
        }
    }

    private static function minutes(string $from, string $to): int
    {
        return (int) CarbonImmutable::parse("2000-01-01 {$from}")->diffInMinutes(CarbonImmutable::parse("2000-01-01 {$to}"), false);
    }

    private static function duration(int $minutes): string
    {
        return $minutes % 60 === 0 ? ($minutes / 60).' hours' : "{$minutes} minutes";
    }

    private static function name(Room $room): string
    {
        return (string) ($room->page()->title ?? 'The room');
    }
}
