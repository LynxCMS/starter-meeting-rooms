<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Bookings\Booking;
use App\Bookings\Bookings;
use App\Mail\MeetingMail;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use LynxCms\Core\Support\Value;
use Zap\Models\Schedule;

/**
 * Reminders, rooms.reminder_minutes before each meeting, to its organizer and the people invited.
 * The scheduler asks every minute (rooms:send-reminders); each meeting date is reminded once,
 * still within a few minutes if the scheduler missed one, and not at all when it was booked after
 * its reminder was due (its booking mail just went out). Cancelled meetings are gone, so they are
 * never reminded; a private one's subject leaves out its title.
 */
final class Reminders
{
    private const int LATE_MINUTES = 5;

    /** @return int how many meetings were reminded */
    public function send(): int
    {
        $lead = Value::int(config('rooms.reminder_minutes'), 15);
        $sent = 0;

        foreach ($lead > 0 ? Room::query()->get() : [] as $room) {
            $now = CarbonImmutable::now($room->timeZone());

            foreach (Bookings::on($room, $now->toDateString()) as $booking) {
                if ($this->isDue($booking, $room, $now, $lead) && Cache::add("rooms:reminded:{$booking->id}:{$booking->date}", true, $now->addDay())) {
                    $this->remind($booking, $lead);
                    $sent++;
                }
            }
        }

        return $sent;
    }

    private function isDue(Booking $booking, Room $room, CarbonImmutable $now, int $lead): bool
    {
        if ($booking->isBlocked) {
            return false;
        }

        $due = CarbonImmutable::parse("{$booking->date} {$booking->from}", $room->timeZone())->subMinutes($lead);
        $bookedAt = Schedule::query()->whereKey($booking->id)->value('created_at');

        return $now->greaterThanOrEqualTo($due) && $now->lessThan($due->addMinutes(self::LATE_MINUTES))
            && ($bookedAt === null || CarbonImmutable::parse(Value::string($bookedAt))->lessThan($due));
    }

    private function remind(Booking $booking, int $lead): void
    {
        $schedule = Schedule::query()->with('periods')->find($booking->id);

        if (! $schedule instanceof Schedule) {
            return;
        }

        $summary = Summary::of(Meeting::of($schedule), $booking->date);

        foreach (User::query()->whereIn('id', $booking->people())->get() as $person) {
            $isOrganizer = (string) $person->id === (string) $booking->organizerId;
            Mail::to($person)->locale($booking->language)->queue(new MeetingMail(MeetingMail::REMINDER, $summary, $isOrganizer, minutes: $lead));
        }
    }
}
