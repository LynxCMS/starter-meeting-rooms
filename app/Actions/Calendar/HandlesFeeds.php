<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Actions\Bookings\HandlesBookings;
use App\Models\CalendarFeed;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/**
 * What the feed actions share: whose feed the input names. A room's (managers only), or, with no
 * room, the feed of the person running the action.
 */
trait HandlesFeeds
{
    use HandlesBookings;

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function rules(): array
    {
        return ['room' => ['sometimes', 'nullable']];
    }

    public static function schema(JsonSchema $schema): array
    {
        return ['room' => $schema->string()->description('Path or id of a room page, for its feed (managers only). Leave out for your own feed.')];
    }

    /** @return array{page_id: int|null, user_id: int|null, name: string} */
    private function owner(Input $input, ActionContext $context): array
    {
        if ($input->get('room') !== null) {
            $context->authorize('rooms.manage');
            $room = $this->room($input->get('room'));

            return ['page_id' => $room->page_id, 'user_id' => null, 'name' => 'the room '.$room->page()?->title];
        }

        if (! is_numeric($context->actor->id)) {
            throw ValidationException::withMessages(['room' => 'Only people have a calendar of their own; name a room.']);
        }

        return ['page_id' => null, 'user_id' => (int) $context->actor->id, 'name' => 'your meetings'];
    }

    /** @param  array{page_id: int|null, user_id: int|null}  $owner */
    private function current(array $owner): ?CalendarFeed
    {
        return $owner['page_id'] !== null ? CalendarFeed::forRoom($owner['page_id']) : CalendarFeed::forUser((int) $owner['user_id']);
    }
}
