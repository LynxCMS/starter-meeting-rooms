<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/** Ends a meeting under way at this minute, usually from its door screen, freeing the room. */
final class EndEarly extends Action
{
    use HandlesMeetingsNow;

    public static function name(): string
    {
        return 'rooms.end_early';
    }

    public static function description(): string
    {
        return 'End a meeting under way now, so its room is free for the rest of its time.';
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
            'screen' => $schema->string()->description('The door screen ending it, which must be at the booking\'s room.'),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        [$schedule, $booking, $room] = $this->meeting($input, $context);
        $now = $this->now($room);

        if ($now->lessThanOrEqualTo($this->at($booking, $booking->from, $room)) || $now->greaterThanOrEqualTo($this->at($booking, $booking->to, $room))) {
            throw ValidationException::withMessages(['booking' => 'Only a meeting under way can end early.']);
        }

        $this->endNow($schedule, $now, ['ended_early_at' => $now->toIso8601String()]);
        $context->changes->updated('booking', $booking->id, 'End '.$booking->publicTitle().' in '.$booking->room()?->title.' early, at '.$now->format('H:i'));

        return Booking::fromSchedule($schedule->refresh()->load('periods'));
    }
}
