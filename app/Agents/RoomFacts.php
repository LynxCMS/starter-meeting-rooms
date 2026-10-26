<?php

declare(strict_types=1);

namespace App\Agents;

use App\Bookings\Booking;
use App\Models\Room;
use App\Models\User;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Permissions\Authorizer;
use LynxCms\Core\Support\Value;

/**
 * Rooms and bookings as the assistants' read tools answer them. A private booking reads "Busy",
 * with no organizer, unless the person asking organizes it, is invited, or manages rooms: what
 * screens and the staff's pages show them too.
 */
final class RoomFacts
{
    /** @return array{id: int, room: string, title: string, office: string, capacity: int, floor: string, equipment: list<string>, time_zone: string, today: string} */
    public static function of(Page $page, Room $room): array
    {
        return [
            'id' => (int) $page->id,
            'room' => self::path($page),
            'title' => (string) $page->title,
            'office' => (string) ($page->office->title ?? ''),
            'capacity' => Value::int($page->capacity),
            'floor' => (string) $page->floor,
            'equipment' => $page->equipment->values(),
            'time_zone' => $room->timeZone(),
            'today' => $room->today()->toDateString(),
        ];
    }

    /** The page's path as the booking actions take it: "/rooms/lodge/". */
    public static function path(Page $page): string
    {
        return '/'.trim((string) $page->path, '/').'/';
    }

    /**
     * @param  list<Booking>  $bookings
     * @return list<array<string, mixed>>
     */
    public static function bookings(array $bookings, Actor $actor): array
    {
        $isManager = app(Authorizer::class)->allows($actor, 'rooms.manage');
        $names = self::names($bookings);

        return array_map(function (Booking $booking) use ($actor, $isManager, $names): array {
            $isTheirs = self::concerns($booking, $actor);
            $mayRead = ! $booking->isPrivate || $isTheirs || $isManager;

            return [
                'booking' => $booking->id,
                'from' => $booking->from,
                'to' => $booking->to,
                'title' => $mayRead ? $booking->title : $booking->publicTitle(),
                'organizer' => $mayRead && ! $booking->isBlocked ? ($names[(string) $booking->organizerId] ?? null) : null,
                'blocked' => $booking->isBlocked,
                'private' => $booking->isPrivate,
                'repeats' => $booking->repeats,
                'checked_in' => $booking->checkedInAt !== null,
                'yours' => $isTheirs,
            ];
        }, $bookings);
    }

    /** Whether the person asking organizes the booking or is invited to it. */
    private static function concerns(Booking $booking, Actor $actor): bool
    {
        return is_numeric($actor->id) && in_array((int) $actor->id, $booking->people(), true);
    }

    /**
     * @param  list<Booking>  $bookings
     * @return array<string, string> organizers' names, by user id
     */
    private static function names(array $bookings): array
    {
        $ids = array_values(array_unique(array_filter(array_map(fn (Booking $booking): ?string => $booking->organizerId === null ? null : (string) $booking->organizerId, $bookings))));

        return User::query()->whereIn('id', $ids)->pluck('name', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [(string) $id => Value::string($name)])->all();
    }
}
