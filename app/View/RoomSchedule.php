<?php

declare(strict_types=1);

namespace App\View;

use App\Bookings\Bookings;
use App\Models\Room;
use App\Rooms\FreeTimes;
use Illuminate\View\View;
use LynxCms\Core\Pages\Page;

/**
 * For the room page: the next days the room opens (up to five, within two weeks), with its
 * meetings and blocked times and when it is free on each, so the view only prints.
 */
final class RoomSchedule
{
    private const int DAYS_SHOWN = 5;

    private const int DAYS_LOOKED_AT = 14;

    public function compose(View $view): void
    {
        $page = $view->getData()['page'] ?? null;
        $room = $page instanceof Page ? Room::forPage($page) : null;
        $days = [];

        for ($add = 0; $room !== null && $add < self::DAYS_LOOKED_AT && count($days) < self::DAYS_SHOWN; $add++) {
            $day = $room->today()->addDays($add);

            if ($this->opensOn($room, $day->toDateString())) {
                $days[] = ['date' => $day, 'free' => FreeTimes::on($room, $day->toDateString()), 'bookings' => Bookings::on($room, $day->toDateString())];
            }
        }

        $view->with('days', $days);
    }

    /** Open that day, even when fully booked: Zap lists its slots, free or not. */
    private function opensOn(Room $room, string $date): bool
    {
        return $room->getBookableSlots($date, 15, 0) !== [];
    }
}
