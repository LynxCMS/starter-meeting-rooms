<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\Bookings;
use App\Bookings\Series;
use App\Calendar\Invitations;
use App\Calendar\Meeting;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Support\Value;

/**
 * Releases no-shows: in rooms that need a check in, a meeting nobody checked into within
 * rooms.check_in_minutes of its start is ended now, so the rest of its time is free. The scheduler
 * runs it every minute (rooms:release-no-shows); each release shows in the activity log, and its
 * organizer is told by mail.
 */
final class ReleaseNoShows extends Action
{
    use HandlesMeetingsNow;

    public static function name(): string
    {
        return 'rooms.release_no_shows';
    }

    public static function description(): string
    {
        return 'Free the rest of meetings nobody checked into, in rooms that need a check in.';
    }

    public static function permission(): string
    {
        return 'rooms.manage';
    }

    public static function rules(): array
    {
        return [];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [];
    }

    /** @return list<int> the bookings released */
    public function handle(Input $input, ActionContext $context): array
    {
        $grace = Value::int(config('rooms.check_in_minutes'), 10);
        $released = [];

        foreach (Room::query()->get() as $room) {
            if (! Value::bool($room->page()?->check_in)) {
                continue;
            }

            $now = $this->now($room);

            foreach (Bookings::on($room, $now->toDateString()) as $booking) {
                if ($booking->isBlocked || $booking->checkedInAt !== null || ! $this->isNoShow($booking, $room, $now, $grace)) {
                    continue;
                }

                $schedule = Series::detach($this->schedule($booking->id), $booking->date);
                $this->endNow($schedule, $now, ['released_at' => $now->toIso8601String()]);
                Invitations::released($context, Meeting::of($schedule), $booking->date);
                $context->changes->updated('booking', $booking->id, 'Release '.$booking->publicTitle().' in '.$room->page()?->title.': nobody checked in by '.$this->at($booking, $booking->from, $room)->addMinutes($grace)->format('H:i'));
                $released[] = $booking->id;
            }
        }

        return $released;
    }

    private function isNoShow(Booking $booking, Room $room, CarbonImmutable $now, int $grace): bool
    {
        return $now->greaterThanOrEqualTo($this->at($booking, $booking->from, $room)->addMinutes($grace))
            && $now->lessThan($this->at($booking, $booking->to, $room));
    }
}
