<?php

declare(strict_types=1);

namespace App\Screens;

use App\Bookings\Booking;
use App\Bookings\Bookings;
use App\Bookings\Week;
use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * What a screen shows, as the JSON it polls: today's meetings in its rooms (an office's for a
 * lobby, one for a door), in the office's time zone, with the announcements running now and the
 * firm's branding. Only what a screen may show: private meetings read "Busy", with no organizer.
 */
final class ScreenState
{
    /** @return array<string, mixed> */
    public static function of(Page $screen): array
    {
        // A kiosk link is the screen's credential: its rooms sit behind the Rooms password, which
        // would hide them (and references to them) from a request with no session.
        return app(PageVisibility::class)->withoutRules(
            fn (): array => lynx()->language(self::language($screen), fn (): array => self::build($screen)),
        );
    }

    /** The language the screen speaks: its own setting, else the site's default. */
    public static function language(Page $screen): string
    {
        $code = Value::string($screen->screen_language?->value);

        return lynx()->languages->find($code) !== null ? $code : (string) lynx()->languages->default()->code;
    }

    /** @return array<string, mixed> */
    private static function build(Page $screen): array
    {
        $office = $screen->office instanceof Page ? $screen->office : null;
        $zone = Week::zone($office);
        $now = CarbonImmutable::now($zone);
        $isDoor = $screen->kind?->value === 'door';

        return [
            'kind' => $isDoor ? 'door' : 'lobby',
            'title' => (string) $screen->title,
            'office' => $office === null ? '' : (string) $office->city,
            'date' => $now->toDateString(),
            'minute' => $now->hour * 60 + $now->minute,
            'second' => $now->second,
            'rooms' => array_map(fn (Page $room): array => self::room($room, $now, withPhoto: $isDoor), self::rooms($screen, $office)),
            'announcements' => Value::bool($screen->show_announcements) ? self::announcements($now) : [],
        ];
    }

    /** @return list<Page> */
    private static function rooms(Page $screen, ?Page $office): array
    {
        if ($screen->kind?->value === 'door') {
            return $screen->room instanceof Page ? [$screen->room] : [];
        }

        $chosen = $screen->rooms->all();

        return $chosen !== [] ? $chosen : ($office === null ? [] : Week::rooms((int) $office->id)->all());
    }

    /** @return array<string, mixed> */
    private static function room(Page $page, CarbonImmutable $now, bool $withPhoto): array
    {
        $room = Room::forPage($page);
        $bookings = $room instanceof Room ? Bookings::on($room, $now->toDateString()) : [];

        return [
            'id' => (int) $page->id,
            'name' => (string) $page->title,
            'color' => (string) $page->color,
            'capacity' => Value::int($page->capacity),
            'floor' => (string) ($page->floor ?? ''),
            'checkIn' => Value::bool($page->check_in),
            'hours' => self::hoursOn($page, $now),
            'photo' => $withPhoto ? $page->photo?->size(900, 600)->url() : null,
            'meetings' => array_map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'from' => $booking->from,
                'to' => $booking->to,
                // Screens say "Busy" in their own language for private meetings, from their strings.
                'title' => $booking->isBlocked || $booking->isPrivate ? '' : $booking->title,
                'private' => $booking->isPrivate,
                'blocked' => $booking->isBlocked,
                'checkedIn' => $booking->checkedInAt !== null,
            ], $bookings),
        ];
    }

    /** @return array{opens: string, closes: string}|null when the room opens today */
    private static function hoursOn(Page $page, CarbonImmutable $now): ?array
    {
        foreach ($page->opening_hours as $item) {
            if ($item->weekday?->value === strtolower($now->englishDayOfWeek)) {
                return ['opens' => (string) $item->opens, 'closes' => (string) $item->closes];
            }
        }

        return null;
    }

    /** @return list<array{title: string, body: string, image: string|null}> announcements showing now */
    private static function announcements(CarbonImmutable $now): array
    {
        $pages = app(PageVisibility::class)->withoutRules(fn (): PageArray => lynx()->pages->find('template=announcement, include=hidden, sort=-starts'));
        $showing = [];

        foreach ($pages as $page) {
            $starts = $page->starts instanceof \DateTimeInterface ? CarbonImmutable::instance($page->starts) : null;
            $ends = $page->ends instanceof \DateTimeInterface ? CarbonImmutable::instance($page->ends) : null;

            if (($starts === null || $starts->lessThanOrEqualTo($now)) && ($ends === null || $ends->greaterThan($now))) {
                $showing[] = ['title' => (string) $page->title, 'body' => trim(strip_tags((string) $page->body)), 'image' => $page->photo?->size(800, 500)->url()];
            }
        }

        return $showing;
    }
}
