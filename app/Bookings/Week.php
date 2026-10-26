<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SiteSchemaSeeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * A working week of bookings, as the admin's booking screens show it: Monday to Friday in an
 * office's time zone, and what each room has on each day.
 */
final class Week
{
    public const int WORKING_DAYS = 5;

    /** The Monday of the week a date falls in (today in the zone when none is given), as Y-m-d. */
    public static function monday(?string $date, string $zone): string
    {
        $day = $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? CarbonImmutable::parse($date, $zone) : CarbonImmutable::now($zone);

        return $day->startOfWeek()->toDateString();
    }

    /** @return list<CarbonImmutable> */
    public static function days(string $monday): array
    {
        $start = CarbonImmutable::parse($monday);

        return array_map(fn (int $add): CarbonImmutable => $start->addDays($add), range(0, self::WORKING_DAYS - 1));
    }

    /** An office's IANA time zone, from its time zone field. */
    public static function zone(?Page $office): string
    {
        $value = Value::string($office?->time_zone?->value);

        return SiteSchemaSeeder::TIME_ZONES[$value]['zone'] ?? Value::string(config('app.timezone'), 'UTC');
    }

    /** Every office, and an office's rooms, whatever visitors may see: rooms sit behind a password. */
    public static function offices(): PageArray
    {
        return app(PageVisibility::class)->withoutRules(fn (): PageArray => lynx()->pages->find('template=office, sort=sort'));
    }

    public static function rooms(int $office): PageArray
    {
        return app(PageVisibility::class)->withoutRules(fn (): PageArray => lynx()->pages->find("template=room, office={$office}, sort=title"));
    }

    /**
     * What each room has on each day, by room page id and date.
     *
     * @param  iterable<Page>  $rooms
     * @param  list<CarbonImmutable>  $days
     * @return array<int, array<string, list<Booking>>>
     */
    public static function bookings(iterable $rooms, array $days): array
    {
        $grid = [];

        foreach ($rooms as $page) {
            $room = Room::forPage($page);

            foreach ($days as $day) {
                $grid[(int) $page->id][$day->toDateString()] = $room instanceof Room ? Bookings::on($room, $day->toDateString()) : [];
            }
        }

        return $grid;
    }

    /**
     * The names of the people who organized these bookings, by user id.
     *
     * @param  array<int, array<string, list<Booking>>>  $grid
     * @return array<string, string>
     */
    public static function organizers(array $grid): array
    {
        $ids = [];

        foreach ($grid as $days) {
            foreach ($days as $bookings) {
                foreach ($bookings as $booking) {
                    if ($booking->organizerId !== null) {
                        $ids[] = (string) $booking->organizerId;
                    }
                }
            }
        }

        return User::query()->whereIn('id', array_unique($ids))->pluck('name', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [(string) $id => Value::string($name)])->all();
    }
}
