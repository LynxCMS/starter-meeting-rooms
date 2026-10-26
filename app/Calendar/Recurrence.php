<?php

declare(strict_types=1);

namespace App\Calendar;

use Carbon\CarbonImmutable;

/**
 * A series' repeat as dates and as an iCalendar RRULE, both read the way BookRoom builds Zap's
 * pattern: every week or every two weeks on the first date's weekday, or every month on the same
 * ordinal weekday ("the second Monday"; from the 29th on, "the last").
 */
final readonly class Recurrence
{
    /** No series runs longer than a year; this bounds the loop all the same. */
    private const int MAX_DATES = 60;

    private const array ORDINALS = [1, 2, 3, 4, -1];

    public function __construct(
        public string $repeat,
        public string $first,
        public string $until,
    ) {}

    /** @return list<string> every date of the series, as YYYY-MM-DD */
    public function dates(): array
    {
        $first = CarbonImmutable::parse($this->first);
        $last = CarbonImmutable::parse($this->until)->toDateString();
        $dates = [];

        for ($step = 0; count($dates) < self::MAX_DATES; $step++) {
            $date = $this->nth($first, $step)->toDateString();

            if ($date > $last) {
                break;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    /** "FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;UNTIL=…Z", ending at the close of the last day in $zone. */
    public function rrule(string $zone): string
    {
        $first = CarbonImmutable::parse($this->first);
        $day = strtoupper(substr($first->englishDayOfWeek, 0, 2));
        $until = CarbonImmutable::parse($this->until.' 23:59:59', $zone)->utc()->format('Ymd\THis\Z');

        $rule = match ($this->repeat) {
            'weekly' => "FREQ=WEEKLY;BYDAY={$day}",
            'biweekly' => "FREQ=WEEKLY;INTERVAL=2;BYDAY={$day}",
            default => 'FREQ=MONTHLY;BYDAY='.self::ordinal($first).$day,
        };

        return "{$rule};UNTIL={$until}";
    }

    private function nth(CarbonImmutable $first, int $step): CarbonImmutable
    {
        if ($this->repeat !== 'monthly') {
            return $first->addWeeks($step * ($this->repeat === 'biweekly' ? 2 : 1));
        }

        $month = $first->startOfMonth()->addMonthsNoOverflow($step);
        $ordinal = self::ordinal($first);

        return $ordinal === -1 ? $month->lastOfMonth($first->dayOfWeek) : $month->nthOfMonth($ordinal, $first->dayOfWeek);
    }

    /** 1 to 4 for the first to fourth weekday of the month, -1 for the last. */
    private static function ordinal(CarbonImmutable $first): int
    {
        return self::ORDINALS[min(intdiv($first->day - 1, 7), 4)];
    }
}
