<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\Series;
use App\Calendar\Invitations;
use App\Calendar\Meeting;
use App\Events\MeetingChanged;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use Zap\Services\ScheduleService;

/**
 * Cancels a booking, or lifts a blocked time; the organizer or a manager may. For a series: one
 * date (occurrence), which leaves the others, or the whole series. Everyone invited hears of it:
 * a cancellation, or, for one date of a series, the series without it.
 */
final class CancelBooking extends Action
{
    use HandlesBookings;

    public function __construct(private readonly ScheduleService $schedules) {}

    public static function name(): string
    {
        return 'rooms.cancel';
    }

    public static function description(): string
    {
        return 'Cancel a booking, or lift a blocked time, so the room is free then. For a repeating meeting, cancel one date or the whole series.';
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function destructive(): bool
    {
        return true;
    }

    public static function rules(): array
    {
        return [
            'booking' => ['required', 'integer'],
            'occurrence' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'scope' => ['sometimes', Rule::in(['one', 'series'])],
        ];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'booking' => $schema->integer()->description('Id of the booking or blocked time.')->required(),
            'occurrence' => $schema->string()->description('For a repeating meeting: the date (YYYY-MM-DD) to cancel.'),
            'scope' => $schema->string()->enum(['one', 'series'])->description('"one" (default): that date only. "series": every date of the series.'),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        $schedule = $this->schedule($input->get('booking'));
        $booking = Booking::fromSchedule($schedule, $input->string('occurrence'));
        $this->authorizeChange($context, $booking);
        $before = Meeting::of($schedule);

        if ($input->get('scope') === 'series' && $booking->series !== null) {
            foreach (Series::members($schedule) as $member) {
                $this->schedules->delete($member);
            }

            $context->changes->deleted('booking', $booking->id, 'Cancel the series '.$booking->publicTitle().' in '.$booking->room()?->title);
            Invitations::send($context, MeetingChanged::CANCELLED, $before, null);

            return $booking;
        }

        if (Series::isRecurring($schedule)) {
            $occurrence = (string) ($input->string('occurrence') ?? throw ValidationException::withMessages(['occurrence' => 'Say which date of the series to cancel.']));
            Series::skip($schedule, $occurrence);
        } else {
            $this->schedules->delete($schedule);
        }

        $context->changes->deleted('booking', $booking->id, 'Cancel '.$booking->publicTitle().' in '.$booking->room()?->title.' on '.$booking->when());
        $after = $booking->series === null ? null : Meeting::ofSeries($booking->series);
        Invitations::send($context, MeetingChanged::CANCELLED, $before, $after, $after === null ? null : $booking->date);

        return $booking;
    }
}
