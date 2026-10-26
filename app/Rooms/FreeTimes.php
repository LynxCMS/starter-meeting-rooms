<?php

declare(strict_types=1);

namespace App\Rooms;

use App\Bookings\Bookings;
use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;

/**
 * When a room is free on a day, as ranges ("09:00 to 10:00", "11:00 to 18:00"): its opening
 * hours that day, less its bookings and blocked times. These are the times nothing happens in
 * the room, which is what screens show; whether a new meeting fits, with the gap between
 * meetings, is for the booking rules.
 */
final class FreeTimes
{
    /** @return list<array{from: string, to: string}> */
    public static function on(Room $room, string $date): array
    {
        $page = $room->page();
        $weekday = strtolower(CarbonImmutable::parse($date)->format('l'));
        $free = [];

        foreach ($page instanceof Page ? OpeningHours::of($page) : [] as $group) {
            if (in_array($weekday, $group['days'], true)) {
                $free[] = ['from' => $group['opens'], 'to' => $group['closes']];
            }
        }

        foreach (Bookings::on($room, $date) as $booking) {
            $free = self::without($free, $booking->from, $booking->to);
        }

        usort($free, fn (array $a, array $b): int => strcmp($a['from'], $b['from']));

        return $free;
    }

    /**
     * The ranges with [$from, $to) taken out of them.
     *
     * @param  list<array{from: string, to: string}>  $ranges
     * @return list<array{from: string, to: string}>
     */
    private static function without(array $ranges, string $from, string $to): array
    {
        $left = [];

        foreach ($ranges as $range) {
            if ($to <= $range['from'] || $from >= $range['to']) {
                $left[] = $range;

                continue;
            }

            if ($from > $range['from']) {
                $left[] = ['from' => $range['from'], 'to' => $from];
            }

            if ($to < $range['to']) {
                $left[] = ['from' => $to, 'to' => $range['to']];
            }
        }

        return $left;
    }
}
