<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/**
 * The meetings a person organizes that have not ended, in time order, each date of a series on its
 * own: what they may change or cancel. My bookings lists them, and so does the my_bookings tool.
 */
final class Upcoming
{
    /**
     * Day by day from yesterday (time zones differ) for $weeks weeks; ended ones are left out in
     * their room's time zone.
     *
     * @return list<array{booking: Booking, room: Page|null}>
     */
    public static function organizedBy(int|string $person, int $weeks): array
    {
        $upcoming = [];
        $day = CarbonImmutable::yesterday();
        $last = CarbonImmutable::today()->addWeeks($weeks);

        for (; $day->lessThanOrEqualTo($last); $day = $day->addDay()) {
            $date = $day->toDateString();
            $schedules = Schedule::query()->with('periods')
                ->where('schedule_type', ScheduleTypes::APPOINTMENT)
                ->where('metadata->organizer_id', $person)
                ->forDate($date)->get();

            foreach ($schedules as $schedule) {
                if ($schedule instanceof Schedule && Series::matches($schedule, $date)) {
                    $upcoming[] = self::entry(Booking::fromSchedule($schedule, $date));
                }
            }
        }

        return array_values(array_filter($upcoming));
    }

    /** @return array{booking: Booking, room: Page|null}|null when the meeting has ended */
    private static function entry(Booking $booking): ?array
    {
        $room = Room::query()->find($booking->roomId);

        if (! $room instanceof Room || CarbonImmutable::parse("{$booking->date} {$booking->to}", $room->timeZone())->isPast()) {
            return null;
        }

        return ['booking' => $booking, 'room' => $room->page()];
    }
}
