<?php

declare(strict_types=1);

namespace App\Agents;

use App\Bookings\Upcoming;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use LynxCms\Core\Actions\Runner\ActionRunner;
use LynxCms\Core\Agents\AgentReadTool;
use LynxCms\Core\Support\Value;

/**
 * The meetings the person asking organizes, for MCP clients: what they may move, rename or cancel.
 */
final class MyBookingsTool extends AgentReadTool
{
    private const int DEFAULT_WEEKS = 2;

    private const int MAX_WEEKS = 12;

    public static function name(): string
    {
        return 'my_bookings';
    }

    public static function description(): string
    {
        return 'List the meetings you organize that have not ended, in time order, each date of a repeating meeting on its own, with booking ids, rooms and the people invited (emails). '
            .'Change one with rooms_change or cancel it with rooms_cancel, passing the booking id, and for a repeating meeting the date as occurrence and scope "one" or "series".';
    }

    public static function schema(JsonSchema $schema): array
    {
        return ['weeks' => $schema->integer()->description('How many weeks ahead, 1 to '.self::MAX_WEEKS.'. '.self::DEFAULT_WEEKS.' when left out.')];
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public function handle(array $arguments): mixed
    {
        $actor = app(ActionRunner::class)->actor();

        if (! is_numeric($actor->id)) {
            return ['error' => 'Only people have bookings of their own: connect with your own token.'];
        }

        $weeks = max(1, min(self::MAX_WEEKS, Value::int($arguments['weeks'] ?? null, self::DEFAULT_WEEKS)));
        $upcoming = Upcoming::organizedBy((int) $actor->id, $weeks);
        $invited = array_merge([], ...array_map(fn (array $item): array => $item['booking']->attendees, $upcoming));
        $emails = User::query()->whereIn('id', array_unique($invited))->pluck('email', 'id')->all();

        return array_map(fn (array $item): array => [
            'booking' => $item['booking']->id,
            'date' => $item['booking']->date,
            'from' => $item['booking']->from,
            'to' => $item['booking']->to,
            'title' => $item['booking']->title,
            'room' => $item['room'] === null ? null : RoomFacts::path($item['room']),
            'room_title' => (string) $item['room']?->title,
            'private' => $item['booking']->isPrivate,
            'repeats' => $item['booking']->repeats,
            'attendees' => array_values(array_filter(array_map(fn (int $id): ?string => is_string($emails[$id] ?? null) ? $emails[$id] : null, $item['booking']->attendees))),
        ], $upcoming);
    }
}
