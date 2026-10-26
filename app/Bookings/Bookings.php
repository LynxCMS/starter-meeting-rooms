<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Room;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/** Reading bookings and blocked times: what a room has on a day, in time order. */
final class Bookings
{
    /** @return list<Booking> */
    public static function on(Room $room, string $date): array
    {
        $bookings = $room->schedulesForDate($date)
            ->whereIn('schedule_type', [ScheduleTypes::APPOINTMENT, ScheduleTypes::BLOCKED])
            ->with('periods')->get()
            ->filter(fn (Schedule $schedule): bool => Series::matches($schedule, $date))
            ->map(fn (Schedule $schedule): Booking => Booking::fromSchedule($schedule, $date))
            ->sortBy('from')->values()->all();

        return array_values($bookings);
    }
}
