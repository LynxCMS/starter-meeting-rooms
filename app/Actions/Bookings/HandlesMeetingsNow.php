<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\Series;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use Zap\Models\Schedule;
use Zap\Models\SchedulePeriod;

/**
 * What the actions on a meeting under way share (check in, end early, releasing no-shows): the
 * room's clock, the booking a door screen may touch, and freeing the rest of a meeting.
 */
trait HandlesMeetingsNow
{
    use HandlesBookings;

    /**
     * The booking to act on. A door screen (screen given) may only touch its own room's meetings;
     * people must be its organizer or a manager; the system and the scheduler may act on any.
     *
     * @return array{Schedule, Booking, Room}
     */
    private function meeting(Input $input, ActionContext $context): array
    {
        $schedule = $this->schedule($input->get('booking'));
        $booking = Booking::fromSchedule($schedule);

        if ($booking->isBlocked) {
            throw ValidationException::withMessages(['booking' => 'A blocked time is not a meeting.']);
        }

        $room = Room::query()->findOrFail($booking->roomId);

        if ($input->get('screen') !== null && $this->doorRoomId($input->get('screen')) !== $room->page_id) {
            throw ValidationException::withMessages(['screen' => 'This screen is not at that room\'s door.']);
        }

        if (! in_array($context->actor->kind, [Actor::SYSTEM, Actor::CLI], true)) {
            $this->authorizeChange($context, $booking);
        }

        // Today's meeting of a series becomes one of its own, so checking in or ending it leaves
        // the series' other dates alone.
        if (Series::isRecurring($schedule)) {
            $schedule = Series::detach($schedule, $this->now($room)->toDateString());
            $booking = Booking::fromSchedule($schedule);
        }

        return [$schedule, $booking, $room];
    }

    private function now(Room $room): CarbonImmutable
    {
        return CarbonImmutable::now($room->timeZone());
    }

    private function at(Booking $booking, string $time, Room $room): CarbonImmutable
    {
        return CarbonImmutable::parse("{$booking->date} {$time}", $room->timeZone());
    }

    /**
     * Ends a meeting at this minute, so the rest of the room's time is free, and notes why in the
     * booking's metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function endNow(Schedule $schedule, CarbonImmutable $now, array $metadata = []): void
    {
        $period = $schedule->periods->first();

        if ($period instanceof SchedulePeriod) {
            $period->forceFill(['end_time' => $now->format('H:i').':00'])->save();
        }

        if ($metadata !== []) {
            $schedule->forceFill(['metadata' => $metadata + (array) $schedule->metadata])->save();
        }
    }

    /**
     * The room page id a door screen shows. Screens and rooms sit in hidden and password protected
     * branches, so the screen and its room reference are read with visibility rules off.
     */
    private function doorRoomId(mixed $screen): ?int
    {
        return app(PageVisibility::class)->withoutRules(function () use ($screen): ?int {
            $page = lynx()->pages->get(is_numeric($screen) ? (int) $screen : (string) $screen);
            $room = $page instanceof Page && $page->kind?->value === 'door' ? $page->room : null;

            return $room instanceof Page ? (int) $room->id : null;
        });
    }
}
