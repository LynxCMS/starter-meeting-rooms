<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\CalendarFeed;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/**
 * Issues a calendar feed's secret address, for a calendar app to subscribe to, and returns it:
 * the only time it is shown. A room or a person has one live address; a new one replaces the old.
 */
final class IssueCalendarFeed extends Action
{
    use HandlesFeeds;

    public static function name(): string
    {
        return 'rooms.issue_feed';
    }

    public static function description(): string
    {
        return 'Issue the secret calendar feed address of a room, or your own (replacing the old one), and return it, shown only now.';
    }

    public function handle(Input $input, ActionContext $context): string
    {
        $owner = $this->owner($input, $context);
        [$token, $hash] = CalendarFeed::newToken();
        $replaced = $this->current($owner)?->delete() === true;

        $feed = CalendarFeed::query()->create([
            'page_id' => $owner['page_id'],
            'user_id' => $owner['user_id'],
            'token_hash' => $hash,
            'created_by' => is_numeric($context->actor->id) ? (int) $context->actor->id : null,
        ]);

        $context->changes->created('calendar_feed', $feed->id, ($replaced ? 'Replace' : 'Issue').' the calendar feed of '.$owner['name'], ['room' => $owner['page_id'], 'user' => $owner['user_id']]);

        return route($owner['page_id'] !== null ? 'calendar.room' : 'calendar.me', ['token' => $token]);
    }
}
