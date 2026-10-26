<?php

declare(strict_types=1);

namespace App\Actions\Bookings;

use App\Bookings\Booking;
use App\Bookings\BookingRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Facades\Zap;

/**
 * Blocks a room for a period on one day, for maintenance or an event; managers only. It may last
 * longer than a meeting, and is refused over existing meetings, which a manager cancels first.
 */
final class BlockRoom extends Action
{
    use HandlesBookings;

    public static function name(): string
    {
        return 'rooms.block';
    }

    public static function description(): string
    {
        return 'Block a meeting room for a period on one day, for maintenance or an event, so nobody books it then.';
    }

    public static function permission(): string
    {
        return 'rooms.manage';
    }

    public static function rules(): array
    {
        return ['room' => ['required'], ...self::periodRules(), 'reason' => ['required', 'string', 'max:120']];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'room' => $schema->string()->description('Path or id of the room page.')->required(),
            'date' => $schema->string()->description('Day, as YYYY-MM-DD.')->required(),
            'from' => $schema->string()->description('Start, as HH:MM.')->required(),
            'to' => $schema->string()->description('End, as HH:MM.')->required(),
            'reason' => $schema->string()->description('Why, e.g. "New screen".')->required(),
        ];
    }

    public function handle(Input $input, ActionContext $context): Booking
    {
        $room = $this->room($input->get('room'));
        [$date, $from, $to] = [(string) $input->string('date'), (string) $input->string('from'), (string) $input->string('to')];
        BookingRules::check($room, $date, $from, $to, isBlock: true);

        try {
            $schedule = Zap::for($room)->named(trim((string) $input->string('reason')))->blocked()
                ->from($date)->addPeriod($from, $to)->withMetadata(['organizer_id' => $context->actor->id])->save();
        } catch (ScheduleConflictException $exception) {
            throw BookingRules::conflict($room, $exception);
        }

        $block = Booking::fromSchedule($schedule->load('periods'));
        $context->changes->created('booking', $block->id, 'Block '.$block->room()?->title.' on '.$block->when().': '.$block->title);

        return $block;
    }
}
