<?php

declare(strict_types=1);

namespace App\Agents;

use App\Bookings\Bookings;
use App\Models\Room;
use App\Rooms\FreeTimes;
use App\Rooms\OpeningHours;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use LynxCms\Core\Actions\Runner\ActionRunner;
use LynxCms\Core\Agents\AgentReadTool;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * A room's day for MCP clients: its opening hours, its meetings and blocked times, and when it is
 * free, in its office's time zone.
 */
final class RoomScheduleTool extends AgentReadTool
{
    public static function name(): string
    {
        return 'room_schedule';
    }

    public static function description(): string
    {
        return 'Read one meeting room\'s day: its opening hours, its meetings and blocked times (with booking ids), and the times it is free, in its office\'s time zone. '
            .'Private meetings read "Busy" unless they are yours. Book a free time with rooms_book; change or cancel your own meetings with rooms_change or rooms_cancel, '
            .'passing the booking id (and the date as occurrence for a repeating one).';
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'room' => $schema->string()->description('Path or id of the room page, e.g. "/rooms/lodge/". find_free_rooms lists them.')->required(),
            'date' => $schema->string()->description('Day, as YYYY-MM-DD. Today in the room\'s office when left out.'),
        ];
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public function handle(array $arguments): mixed
    {
        $validator = Validator::make($arguments, ['room' => ['required'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $room = Room::byReference($arguments['room'] ?? null);
        $page = $room?->page();

        if ($validator->fails() || ! $page instanceof Page) {
            return ['error' => $validator->errors()->first() ?: 'There is no room ['.Value::string($arguments['room'] ?? '').']. find_free_rooms lists them.'];
        }

        $date = Value::string($arguments['date'] ?? '') ?: $room->today()->toDateString();
        $weekday = strtolower(CarbonImmutable::parse($date)->format('l'));

        return [
            ...RoomFacts::of($page, $room),
            'now' => CarbonImmutable::now($room->timeZone())->format('H:i'),
            'date' => $date,
            'open' => array_values(array_map(
                fn (array $group): array => ['from' => $group['opens'], 'to' => $group['closes']],
                array_filter(OpeningHours::of($page), fn (array $group): bool => in_array($weekday, $group['days'], true)),
            )),
            'meetings' => RoomFacts::bookings(Bookings::on($room, $date), app(ActionRunner::class)->actor()),
            'free' => FreeTimes::on($room, $date),
        ];
    }
}
