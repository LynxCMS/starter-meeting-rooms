<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\BookingRules;
use App\Bookings\Series;
use App\Calendar\Invitations;
use App\Calendar\Meeting;
use App\Events\MeetingChanged;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use Zap\Builders\ScheduleBuilder;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Facades\Zap;

/**
 * Books a room for a period on one day, for the person running it (managers may book for someone
 * else), or every week, every two weeks or every month (same weekday) until a date: a series. The
 * office's rules first (BookingRules, on the first date), then Zap's overlap and buffer check,
 * which covers every date of a series. The organizer may invite people; everyone concerned is
 * sent the invite, in the language the booking is made in.
 */
final class BookRoom extends Action
{
    use HandlesBookings;

    public static function name(): string
    {
        return 'rooms.book';
    }

    public static function description(): string
    {
        return 'Book a meeting room for a period on one day, with a title, once or repeating, and invite people by email. Private bookings show as "Busy" to others.';
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function rules(): array
    {
        return [
            'room' => ['required'], ...self::periodRules(), 'title' => ['required', 'string', 'max:120'], 'private' => ['sometimes', 'boolean'], 'organizer' => ['sometimes', 'nullable'],
            'repeat' => ['sometimes', 'nullable', Rule::in(Series::REPEATS)],
            'until' => ['required_with:repeat', 'nullable', 'date_format:Y-m-d', 'after:date', 'before_or_equal:'.CarbonImmutable::today()->addYear()->toDateString()],
            ...self::attendeeRules(),
        ];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'room' => $schema->string()->description('Path or id of the room page, e.g. "/rooms/atlas/".')->required(),
            'date' => $schema->string()->description('Day, as YYYY-MM-DD.')->required(),
            'from' => $schema->string()->description('Start, as HH:MM.')->required(),
            'to' => $schema->string()->description('End, as HH:MM.')->required(),
            'title' => $schema->string()->description('What the meeting is.')->required(),
            'private' => $schema->boolean()->description('Show only "Busy" to others.'),
            'organizer' => $schema->string()->description('User id to book for; managers only. Defaults to whoever books.'),
            'repeat' => $schema->string()->enum(Series::REPEATS)->description('Repeat the meeting: weekly, biweekly, or monthly (same weekday, e.g. the second Monday).'),
            'until' => $schema->string()->description('Last day a repeating meeting may fall on, as YYYY-MM-DD (within a year).'),
            'attendees' => $schema->array()->items($schema->string())->description('Emails of colleagues to invite; only when booking for yourself.'),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        $room = $this->room($input->get('room'));
        [$date, $from, $to] = [(string) $input->string('date'), (string) $input->string('from'), (string) $input->string('to')];
        BookingRules::check($room, $date, $from, $to);
        $organizer = $input->get('organizer') !== null && $this->isManager($context->actor) ? $input->get('organizer') : $context->actor->id;

        $repeat = $input->get('repeat');
        $metadata = [
            'organizer_id' => $organizer, 'private' => (bool) $input->get('private', false),
            'attendees' => $this->attendees($input, $context, $organizer) ?? [], 'language' => (string) lynx()->languages->current()->code,
        ];

        try {
            $builder = Zap::for($room)->named(trim((string) $input->string('title')))->appointment()->from($date)->addPeriod($from, $to);

            if (is_string($repeat)) {
                $builder = self::repeat($builder->to((string) $input->string('until')), $repeat, CarbonImmutable::parse($date));
                $metadata += ['series' => (string) Str::uuid(), 'repeat' => $repeat, 'first' => $date, 'until' => (string) $input->string('until')];
            }

            $schedule = $builder->withMetadata($metadata)->save();
        } catch (ScheduleConflictException $exception) {
            throw BookingRules::conflict($room, $exception);
        }

        $booking = Booking::fromSchedule($schedule->load('periods'));
        $repeats = is_string($repeat) ? ', '.$repeat.' until '.$input->string('until') : '';
        $context->changes->created('booking', $booking->id, 'Book '.$booking->room()?->title.' on '.$booking->when().$repeats.': '.$booking->publicTitle(), ['room' => $booking->roomId, 'date' => $date, 'from' => $from, 'to' => $to, 'repeat' => $repeat, 'attendees' => $booking->attendees]);
        Invitations::send($context, MeetingChanged::BOOKED, null, Meeting::of($schedule));

        return $booking;
    }

    /** Zap's pattern for a repeat, from the first date's weekday ("second Monday" for monthly). */
    private static function repeat(ScheduleBuilder $builder, string $repeat, CarbonImmutable $first): ScheduleBuilder
    {
        $day = strtolower($first->englishDayOfWeek);
        $ordinal = ['first', 'second', 'third', 'fourth', 'last'][min(intdiv($first->day - 1, 7), 4)];

        return match ($repeat) {
            'weekly' => $builder->weekly([$day]),
            'biweekly' => $builder->biweekly([$day], $first),
            default => $builder->{$ordinal.ucfirst($day).'OfMonth'}(),
        };
    }
}
