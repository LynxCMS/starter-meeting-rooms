<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/** Revokes a room's calendar feed address, or your own: calendars subscribed to it stop updating. */
final class RevokeCalendarFeed extends Action
{
    use HandlesFeeds;

    public static function name(): string
    {
        return 'rooms.revoke_feed';
    }

    public static function description(): string
    {
        return 'Revoke the calendar feed address of a room, or your own, so calendars subscribed to it stop updating.';
    }

    public static function destructive(): bool
    {
        return true;
    }

    public function handle(Input $input, ActionContext $context): bool
    {
        $owner = $this->owner($input, $context);
        $feed = $this->current($owner);

        if ($feed === null) {
            return false;
        }

        $feed->delete();
        $context->changes->deleted('calendar_feed', $feed->id, 'Revoke the calendar feed of '.$owner['name']);

        return true;
    }
}
