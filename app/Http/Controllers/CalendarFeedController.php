<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Calendar\Feed;
use App\Models\CalendarFeed;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * The calendar feeds calendar apps subscribe to, without a session: the secret address is the
 * credential. An unknown or revoked one is a plain 404.
 */
final class CalendarFeedController
{
    private const array HEADERS = [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'Content-Disposition' => 'inline; filename="calendar.ics"',
        'Cache-Control' => 'no-store, private',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    public function room(string $token): Response
    {
        $feed = $this->feed($token);
        $room = $feed?->page_id === null ? null : Room::query()->where('page_id', $feed->page_id)->first();

        return $room instanceof Room ? response(Feed::ofRoom($room), 200, self::HEADERS) : $this->invalid();
    }

    public function me(string $token): Response
    {
        $feed = $this->feed($token);
        $person = $feed?->user_id === null ? null : User::query()->find($feed->user_id);

        return $person instanceof User ? response(Feed::ofPerson($person), 200, self::HEADERS) : $this->invalid();
    }

    private function feed(string $token): ?CalendarFeed
    {
        $feed = CalendarFeed::findByToken($token);
        $feed?->forceFill(['last_seen_at' => now()])->save();

        return $feed;
    }

    private function invalid(): Response
    {
        return response('Not found', 404, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
    }
}
