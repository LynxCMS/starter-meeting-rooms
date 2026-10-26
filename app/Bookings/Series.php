<?php

declare(strict_types=1);

namespace App\Bookings;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Support\Value;
use Zap\Data\FrequencyConfig;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

/**
 * Recurring meetings. A series is one recurring Zap appointment, its metadata naming the series.
 * Zap cannot change one date of a recurring schedule, so a date is taken out by splitting it:
 * the schedule ends the day before, a copy starts the day after, both with the same series. A
 * date that changes becomes a one-off appointment of its own, still naming the series.
 */
final class Series
{
    /** How a series may repeat: every week, every two weeks, every month on the same weekday. */
    public const array REPEATS = ['weekly', 'biweekly', 'monthly'];

    /**
     * Every schedule of a booking's series (its splits and one-offs), or just itself.
     *
     * @return list<Schedule>
     */
    public static function members(Schedule $schedule): array
    {
        $series = self::id($schedule);

        if ($series === null) {
            return [$schedule];
        }

        $members = Schedule::query()->with('periods')->where('metadata->series', $series)->get()->all();

        return array_values(array_filter($members, fn (mixed $member): bool => $member instanceof Schedule));
    }

    public static function id(Schedule $schedule): ?string
    {
        $series = Value::map($schedule->metadata)['series'] ?? null;

        return is_string($series) && $series !== '' ? $series : null;
    }

    public static function isRecurring(Schedule $schedule): bool
    {
        return (bool) $schedule->is_recurring;
    }

    /** Whether a schedule has a meeting on a date (Zap's own reading of its pattern). */
    public static function occursOn(Schedule $schedule, string $date): bool
    {
        return Schedule::query()->whereKey($schedule->getKey())->forDate($date)->exists() && self::matches($schedule, $date);
    }

    /**
     * Zap's forDate() matches every weekday of a "second Monday of the month" pattern, and leaves
     * the ordinal to the pattern itself: its shouldCreateRecurringInstance() has the last word.
     */
    public static function matches(Schedule $schedule, string $date): bool
    {
        $config = $schedule->frequency_config;

        return ! self::isRecurring($schedule) || ! $config instanceof FrequencyConfig
            || $config->shouldCreateRecurringInstance($schedule, Carbon::parse($date));
    }

    /**
     * Takes one date out of a recurring schedule: it ends the day before (or goes, when that was
     * its first date), and a copy carries on from the day after (unless that was its last).
     */
    public static function skip(Schedule $schedule, string $date): void
    {
        if (! self::isRecurring($schedule) || ! self::occursOn($schedule, $date)) {
            throw ValidationException::withMessages(['occurrence' => "This series has no meeting on {$date}."]);
        }

        $day = CarbonImmutable::parse($date);
        $start = CarbonImmutable::parse($schedule->start_date);
        $end = $schedule->end_date === null ? null : CarbonImmutable::parse($schedule->end_date);

        if ($end === null || $day->addDay()->lessThanOrEqualTo($end)) {
            self::copyFrom($schedule, $day->addDay());
        }

        if ($day->greaterThan($start)) {
            $schedule->forceFill(['end_date' => $day->subDay()->toDateString()])->save();
        } else {
            $schedule->periods()->delete();
            $schedule->delete();
        }
    }

    /**
     * One date of a recurring meeting as a one-off appointment of its own, with the same title,
     * time and details, so it can be checked in, ended early or changed alone. A meeting that does
     * not repeat is returned as it is.
     */
    public static function detach(Schedule $schedule, string $date): Schedule
    {
        if (! self::isRecurring($schedule)) {
            return $schedule;
        }

        $period = $schedule->periods->first();
        $from = substr(Value::string($period?->start_time), 0, 5);
        $to = substr(Value::string($period?->end_time), 0, 5);
        self::skip($schedule, $date);

        return Zap::for($schedule->schedulable)->named(Value::string($schedule->name))->appointment()
            ->from($date)->addPeriod($from, $to)
            ->withMetadata(['occurrence' => $date] + (array) $schedule->metadata)->save()->load('periods');
    }

    /** "every week", "every 2 weeks", "every month" for a recurring schedule, null otherwise. */
    public static function pattern(Schedule $schedule): ?string
    {
        if (! self::isRecurring($schedule)) {
            return null;
        }

        $frequency = $schedule->frequency instanceof \BackedEnum ? $schedule->frequency->value : Value::string($schedule->frequency);

        return match ($frequency) {
            'weekly' => 'weekly',
            'biweekly' => 'biweekly',
            default => 'monthly',
        };
    }

    /** A copy of a recurring schedule, with its periods, starting on another day. */
    private static function copyFrom(Schedule $schedule, CarbonImmutable $day): void
    {
        $copy = $schedule->replicate();
        $copy->forceFill(['start_date' => $day->toDateString()])->save();

        foreach ($schedule->periods as $period) {
            $period->replicate()->forceFill(['schedule_id' => $copy->getKey(), 'date' => $day->toDateString()])->save();
        }
    }
}
