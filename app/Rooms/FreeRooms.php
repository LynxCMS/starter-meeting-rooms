<?php

declare(strict_types=1);

namespace App\Rooms;

use App\Models\Room;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * The rooms that suit a meeting on a day, with the times it can start in each: enough seats, the
 * equipment asked for, and free for its length (Slots). What the booking page lists and what
 * assistants get from find_free_rooms; rooms.book still decides.
 */
final class FreeRooms
{
    public const array EQUIPMENT = ['screen', 'video', 'whiteboard'];

    /**
     * Rooms that suit, even with no start time left that day. Without a date, each room's today.
     *
     * @param  iterable<Page>  $pages
     * @param  list<string>  $equipment
     * @return list<array{page: Page, room: Room, date: string, starts: list<string>}>
     */
    public static function among(iterable $pages, ?string $date, int $minutes, int $seats, array $equipment): array
    {
        $found = [];

        foreach ($pages as $page) {
            $room = Room::forPage($page);

            if ($room instanceof Room && self::suits($page, $seats, $equipment)) {
                $day = $date ?? $room->today()->toDateString();
                $found[] = ['page' => $page, 'room' => $room, 'date' => $day, 'starts' => Slots::starting($room, $day, $minutes)];
            }
        }

        return $found;
    }

    /** @param  list<string>  $equipment */
    public static function suits(Page $page, int $seats, array $equipment): bool
    {
        return Value::int($page->capacity) >= $seats && array_diff($equipment, $page->equipment->values()) === [];
    }
}
