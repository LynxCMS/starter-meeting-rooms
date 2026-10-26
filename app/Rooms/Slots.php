<?php

declare(strict_types=1);

namespace App\Rooms;

use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * The start times a meeting of some length can take in a room on a day, every quarter hour: inside
 * its free times (FreeTimes), keeping the gap after the meeting before it (rooms.buffer_minutes) and
 * before the one after, and not in the past. What the booking page offers; rooms.book still decides.
 */
final class Slots
{
    public const int STEP_MINUTES = 15;

    /** @return list<string> start times as HH:MM */
    public static function starting(Room $room, string $date, int $minutes): array
    {
        $buffer = Value::int(config('rooms.buffer_minutes'), 0);
        [$opens, $closes] = self::hours($room->page(), $date);
        $now = CarbonImmutable::now($room->timeZone());
        $earliest = $now->toDateString() === $date ? $now->hour * 60 + $now->minute : 0;
        $starts = [];

        foreach (FreeTimes::on($room, $date) as $range) {
            $from = self::minute($range['from']) + (in_array($range['from'], $opens, true) ? 0 : $buffer);
            $to = self::minute($range['to']) - (in_array($range['to'], $closes, true) ? 0 : $buffer);

            for ($start = self::roundUp(max($from, $earliest + 1)); $start + $minutes <= $to; $start += self::STEP_MINUTES) {
                $starts[] = sprintf('%02d:%02d', intdiv($start, 60), $start % 60);
            }
        }

        return $starts;
    }

    /** "10:00" plus minutes, as "10:45". */
    public static function plus(string $time, int $minutes): string
    {
        $total = self::minute($time) + $minutes;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    /**
     * The room's opening and closing times that day: a free time starting at an opening (or ending
     * at a closing) needs no gap there.
     *
     * @return array{list<string>, list<string>}
     */
    private static function hours(?Page $page, string $date): array
    {
        $weekday = strtolower(CarbonImmutable::parse($date)->format('l'));
        $opens = $closes = [];

        foreach ($page instanceof Page ? OpeningHours::of($page) : [] as $group) {
            if (in_array($weekday, $group['days'], true)) {
                $opens[] = $group['opens'];
                $closes[] = $group['closes'];
            }
        }

        return [$opens, $closes];
    }

    private static function minute(string $time): int
    {
        [$hours, $minutes] = array_map(intval(...), explode(':', $time));

        return $hours * 60 + $minutes;
    }

    private static function roundUp(int $minute): int
    {
        return (int) ceil($minute / self::STEP_MINUTES) * self::STEP_MINUTES;
    }
}
