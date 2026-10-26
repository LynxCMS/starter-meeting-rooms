<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Bookings\Series;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use LynxCms\Core\Support\Value;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Builders\ScheduleBuilder;
use Zap\Models\Schedule;

/**
 * Calendar feeds: a room's meetings and blocked times (private ones only "Busy"), or a person's
 * meetings, as organizer or invited, in full. One event per date, from a month back to half a
 * year ahead; calendar apps read them read-only, every quarter of an hour or so.
 */
final class Feed
{
    private const int DAYS_BACK = 30;

    private const int DAYS_AHEAD = 180;

    public static function ofRoom(Room $room): string
    {
        $query = Schedule::query()->where('schedulable_type', $room->getMorphClass())->where('schedulable_id', $room->id)
            ->whereIn('schedule_type', [ScheduleTypes::APPOINTMENT, ScheduleTypes::BLOCKED]);
        $place = Place::of((int) $room->id);
        $name = $place->room.($place->city === '' ? '' : ', '.$place->city);

        return Ics::feed($name, self::dates($query, fn (Occurrence $occurrence): bool => $occurrence->roomId === (int) $room->id), isFull: false);
    }

    public static function ofPerson(User $person): string
    {
        $query = Schedule::query()->where('schedule_type', ScheduleTypes::APPOINTMENT)->where(fn (Builder $query) => $query
            ->where('metadata->organizer_id', $person->id)
            ->orWhereJsonContains('metadata->attendees', (int) $person->id));

        return Ics::feed(Value::string(__('mail.feed_mine', ['name' => $person->name])), self::dates($query, fn (): bool => true), isFull: true);
    }

    /**
     * Each meeting once (a series is read whole), then its dates in the window that $keep keeps.
     *
     * @param  Closure(Occurrence): bool  $keep
     * @return list<array{meeting: Meeting, occurrence: Occurrence}>
     */
    private static function dates(ScheduleBuilder $query, Closure $keep): array
    {
        [$from, $to] = [CarbonImmutable::today()->subDays(self::DAYS_BACK)->toDateString(), CarbonImmutable::today()->addDays(self::DAYS_AHEAD)->toDateString()];
        $schedules = $query->with('periods')->whereDate('start_date', '<=', $to)
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $from))->get();

        $dates = [];
        $seen = [];

        foreach ($schedules as $schedule) {
            $key = $schedule instanceof Schedule ? Series::id($schedule) ?? 'booking-'.$schedule->id : null;

            if ($key === null || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $meeting = Meeting::of($schedule);

            foreach ($meeting->occurrences as $occurrence) {
                if ($occurrence->date >= $from && $occurrence->date <= $to && $keep($occurrence)) {
                    $dates[] = ['meeting' => $meeting, 'occurrence' => $occurrence];
                }
            }
        }

        return $dates;
    }
}
