<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Support\Value;

/**
 * Checks a meeting in, usually from its room's door screen: the room is in use, so the meeting is
 * not released as a no-show. Allowed from a few minutes before it starts until it ends.
 */
final class CheckIn extends Action
{
    use HandlesMeetingsNow;

    public static function name(): string
    {
        return 'rooms.check_in';
    }

    public static function description(): string
    {
        return 'Check a meeting in, so its room is not released as a no-show.';
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function rules(): array
    {
        return ['booking' => ['required', 'integer'], 'screen' => ['sometimes', 'nullable']];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'booking' => $schema->integer()->description('Id of the booking.')->required(),
            'screen' => $schema->string()->description('The door screen checking it in, which must be at the booking\'s room.'),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        [$schedule, $booking, $room] = $this->meeting($input, $context);
        $now = $this->now($room);
        $opens = $this->at($booking, $booking->from, $room)->subMinutes(Value::int(config('rooms.check_in_early_minutes'), 5));

        if ($booking->checkedInAt !== null) {
            return $booking;
        }

        if ($now->lessThan($opens) || $now->greaterThanOrEqualTo($this->at($booking, $booking->to, $room))) {
            throw ValidationException::withMessages(['booking' => 'A meeting is checked in when it is about to start or under way.']);
        }

        $schedule->forceFill(['metadata' => ['checked_in_at' => $now->toIso8601String()] + (array) $schedule->metadata])->save();
        $context->changes->updated('booking', $booking->id, 'Check in '.$booking->publicTitle().' in '.$booking->room()?->title);

        return Booking::fromSchedule($schedule);
    }
}
