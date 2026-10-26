<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\BookingRefused;
use App\Calendar\Meeting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Permissions\Authorizer;
use LynxCms\Core\Support\Value;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/**
 * What the booking actions share: finding rooms and bookings, who may touch a booking, and the
 * people it invites.
 */
trait HandlesBookings
{
    /** How many people one meeting may invite. */
    private const int MAX_ATTENDEES = 30;

    /** @return array<string, list<string>> */
    private static function attendeeRules(): array
    {
        return ['attendees' => ['sometimes', 'nullable', 'array', 'max:'.self::MAX_ATTENDEES], 'attendees.*' => ['email']];
    }

    /**
     * The people invited, by email, as user ids, leaving out the organizer; null when the input
     * leaves them out. Only a meeting's organizer says who is invited, so only they may send it.
     *
     * @return list<int>|null
     */
    private function attendees(Input $input, ActionContext $context, int|string|null $organizer): ?array
    {
        if (! $input->has('attendees') || $input->get('attendees') === null) {
            return null;
        }

        if ((string) $organizer !== (string) $context->actor->id) {
            throw new AuthorizationException('Only its organizer invites people to a meeting.');
        }

        $emails = array_values(array_unique(array_map(fn (string $email): string => mb_strtolower(trim($email)), Value::strings($input->get('attendees')))));

        $people = User::query()->whereIn('email', $emails)->pluck('id', 'email');

        foreach (array_diff($emails, $people->keys()->all()) as $unknown) {
            throw BookingRefused::because('attendees', 'unknown_person', ['email' => $unknown], "There is no one at {$unknown} to invite.");
        }

        return array_values(array_filter(array_map(intval(...), $people->values()->all()), fn (int $id): bool => (string) $id !== (string) $organizer));
    }

    /** A booking's meeting, after a change: the booking alone, or its whole series. */
    private function meetingOf(int $bookingId): ?Meeting
    {
        $schedule = Schedule::query()->with('periods')->find($bookingId);

        return $schedule instanceof Schedule ? Meeting::of($schedule) : null;
    }

    /** @return array<string, list<string>> */
    private static function periodRules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'from' => ['required', 'date_format:H:i'],
            'to' => ['required', 'date_format:H:i'],
        ];
    }

    /** The room a page path or id names. */
    private function room(mixed $reference): Room
    {
        return Room::byReference($reference) ?? throw ValidationException::withMessages(['room' => 'There is no room ['.(is_scalar($reference) ? $reference : '').'].']);
    }

    /** The Zap schedule of a booking or blocked time, with its periods. */
    private function schedule(mixed $id): Schedule
    {
        $id = Value::int($id);
        $schedule = Schedule::query()->with('periods')->whereKey($id)
            ->whereIn('schedule_type', [ScheduleTypes::APPOINTMENT, ScheduleTypes::BLOCKED])->first();

        return $schedule instanceof Schedule ? $schedule : throw ValidationException::withMessages(['booking' => "There is no booking [{$id}]."]);
    }

    /** Organizers change and cancel their own bookings; managers anyone's, and blocked times. */
    private function authorizeChange(ActionContext $context, Booking $booking): void
    {
        $isOwn = ! $booking->isBlocked && $booking->organizerId !== null && (string) $booking->organizerId === (string) $context->actor->id;

        if (! $isOwn && ! $this->isManager($context->actor)) {
            throw new AuthorizationException('Only its organizer or a manager may change this booking.');
        }
    }

    private function isManager(Actor $actor): bool
    {
        return app(Authorizer::class)->allows($actor, 'rooms.manage');
    }
}
