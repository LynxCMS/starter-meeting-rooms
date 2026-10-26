<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Bookings\Booking;

/**
 * One date of a meeting: when and where it is. For a series, $occurrence is the date in the
 * series' pattern it stands for, and $isOverride says it was changed on its own (iCalendar's
 * RECURRENCE-ID): it may have moved to another time, day or room since.
 */
final readonly class Occurrence
{
    public function __construct(
        public int $bookingId,
        public string $occurrence,
        public string $date,
        public string $from,
        public string $to,
        public int $roomId,
        public string $title,
        public bool $isOverride = false,
    ) {}

    public static function of(Booking $booking, ?string $occurrence = null, bool $isOverride = false): self
    {
        return new self($booking->id, $occurrence ?? $booking->date, $booking->date, $booking->from, $booking->to, $booking->roomId, $booking->title, $isOverride);
    }

    public function place(): Place
    {
        return Place::of($this->roomId);
    }
}
