<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\BookingRules;
use App\Bookings\Series;
use App\Calendar\Invitations;
use App\Calendar\Meeting;
use App\Events\MeetingChanged;
use App\Models\Room;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Models\Schedule;
use Zap\Services\ScheduleService;

/**
 * Moves a booking to another time, day or room, or renames it. The organizer or a manager may; the
 * booking rules apply to the new time, and Zap's overlap check leaves the booking itself out. For
 * a series: one date (occurrence) becomes a meeting of its own and changes alone, or the whole
 * series takes a new time, title or privacy (its days and room stay). Its organizer may change
 * who is invited, for the whole meeting; everyone concerned is sent the meeting as it now stands.
 */
final class ChangeBooking extends Action
{
    use HandlesBookings;

    public function __construct(private readonly ScheduleService $schedules) {}

    public static function name(): string
    {
        return 'rooms.change';
    }

    public static function description(): string
    {
        return 'Move a booking to another time, day or room, rename it, or change who is invited. For a repeating meeting, change one date or the whole series.';
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function rules(): array
    {
        return [
            'booking' => ['required', 'integer'],
            'occurrence' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'scope' => ['sometimes', Rule::in(['one', 'series'])],
            'room' => ['sometimes'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'from' => ['sometimes', 'date_format:H:i'],
            'to' => ['sometimes', 'date_format:H:i'],
            'title' => ['sometimes', 'string', 'max:120'],
            'private' => ['sometimes', 'boolean'],
            ...self::attendeeRules(),
        ];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'booking' => $schema->integer()->description('Id of the booking.')->required(),
            'occurrence' => $schema->string()->description('For a repeating meeting: the date (YYYY-MM-DD) of the meeting to change.'),
            'scope' => $schema->string()->enum(['one', 'series'])->description('"one" (default): that date only. "series": every date; only time, title and privacy then.'),
            'room' => $schema->string()->description('Path or id of another room.'),
            'date' => $schema->string()->description('New day, as YYYY-MM-DD.'),
            'from' => $schema->string()->description('New start, as HH:MM.'),
            'to' => $schema->string()->description('New end, as HH:MM.'),
            'title' => $schema->string()->description('New title.'),
            'private' => $schema->boolean()->description('Show only "Busy" to others.'),
            'attendees' => $schema->array()->items($schema->string())->description('Emails of everyone invited, replacing the list; organizer only, and for a whole series.'),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        $schedule = $this->schedule($input->get('booking'));
        $current = Booking::fromSchedule($schedule);
        $this->authorizeChange($context, $current);
        $before = Meeting::of($schedule);
        $attendees = $this->attendees($input, $context, $current->organizerId);
        $isSeries = $input->get('scope') === 'series' && Series::id($schedule) !== null;

        if ($attendees !== null && ! $isSeries && $current->series !== null && $this->differ($attendees, $current->attendees)) {
            throw ValidationException::withMessages(['attendees' => 'Invite people to the whole series, not to one of its dates.']);
        }

        if ($isSeries) {
            $after = $this->changeSeries($schedule, $input, $context, $attendees);
            Invitations::send($context, MeetingChanged::CHANGED, $before, $this->meetingOf($after->id));

            return $after;
        }

        if (Series::isRecurring($schedule)) {
            $occurrence = (string) ($input->string('occurrence') ?? throw ValidationException::withMessages(['occurrence' => 'Say which date of the series to change.']));
            $schedule = Series::detach($schedule, $occurrence);
        }

        $after = $this->changeOne($schedule, $input, $context, $attendees);
        Invitations::send($context, MeetingChanged::CHANGED, $before, $this->meetingOf($after->id), $after->series === null ? null : $after->date);

        return $after;
    }

    /**
     * @param  list<int>  $attendees
     * @param  list<int>  $current
     */
    private function differ(array $attendees, array $current): bool
    {
        sort($attendees);
        sort($current);

        return $attendees !== $current;
    }

    /** @param  list<int>|null  $attendees */
    private function changeOne(Schedule $schedule, Input $input, ActionContext $context, ?array $attendees): Booking
    {
        $before = Booking::fromSchedule($schedule);
        $room = $input->get('room') !== null ? $this->room($input->get('room')) : Room::query()->findOrFail($before->roomId);
        $date = (string) ($input->string('date') ?? $before->date);
        $from = (string) ($input->string('from') ?? $before->from);
        $to = (string) ($input->string('to') ?? $before->to);
        BookingRules::check($room, $date, $from, $to, isBlock: $before->isBlocked);

        $after = $this->update($schedule, $room, $date, $from, $to, $input, $before, $attendees);
        $context->changes->updated('booking', $after->id, 'Change '.$before->publicTitle().' to '.$after->room()?->title.' on '.$after->when(), ['room' => $after->roomId, 'date' => $date, 'from' => $from, 'to' => $to]);

        return $after;
    }

    /**
     * Every schedule of the series takes the new time, title, privacy or attendees, on its own days.
     * A time given applies to every date, even one changed on its own; without one, each keeps its own.
     *
     * @param  list<int>|null  $attendees
     */
    private function changeSeries(Schedule $schedule, Input $input, ActionContext $context, ?array $attendees): Booking
    {
        if ($input->get('date') !== null || $input->get('room') !== null) {
            throw ValidationException::withMessages(['scope' => 'A whole series keeps its days and room. Move one meeting at a time.']);
        }

        $first = Booking::fromSchedule($schedule);
        $after = $first;

        foreach (Series::members($schedule) as $member) {
            $before = Booking::fromSchedule($member);
            $from = (string) ($input->string('from') ?? $before->from);
            $to = (string) ($input->string('to') ?? $before->to);
            $room = Room::query()->findOrFail($before->roomId);
            BookingRules::check($room, $before->date, $from, $to, allowPast: true);
            $updated = $this->update($member, $room, $before->date, $from, $to, $input, $before, $attendees);
            $after = $member->is($schedule) ? $updated : $after;
        }

        $time = $input->string('from') === null && $input->string('to') === null ? '' : ' to '.$after->from.' to '.$after->to;
        $context->changes->updated('booking', $first->id, 'Change the series '.$first->publicTitle().$time, ['series' => $first->series, 'from' => $input->string('from'), 'to' => $input->string('to'), 'attendees' => $attendees]);

        return $after;
    }

    /** @param  list<int>|null  $attendees  null: the people invited stay */
    private function update(Schedule $schedule, Room $room, string $date, string $from, string $to, Input $input, Booking $before, ?array $attendees): Booking
    {
        // A series keeps its first day; a single meeting moves to the new one.
        $day = Series::isRecurring($schedule) ? $schedule->start_date->toDateString() : $date;

        // Zap checks conflicts against the periods it caches on the model; drop the old ones.
        $schedule->unsetRelation('periods');

        try {
            $schedule = $this->schedules->update($schedule, [
                'schedulable_id' => $room->id,
                'name' => trim((string) ($input->string('title') ?? $before->title)),
                'start_date' => $day,
                'metadata' => ['private' => (bool) $input->get('private', $before->isPrivate), 'attendees' => $attendees ?? $before->attendees] + (array) $schedule->metadata,
            ], [['date' => $day, 'start_time' => $from, 'end_time' => $to]]);
        } catch (ScheduleConflictException $exception) {
            throw BookingRules::conflict($room, $exception);
        }

        return Booking::fromSchedule($schedule, Series::isRecurring($schedule) ? $before->date : $date);
    }
}
