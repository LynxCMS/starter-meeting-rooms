<?php

declare(strict_types=1);

namespace App\Rooms;

use App\Models\Room;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Trash;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use Zap\Facades\Zap;

/**
 * Keeps each room page's Room in step: created with the page, removed with its schedules when
 * the page goes to the trash or is deleted, and its availability rewritten from the page's
 * opening hours on every save.
 */
final readonly class RoomSync
{
    public const string TEMPLATE = 'room';

    public function __construct(private Trash $trash) {}

    public function sync(int $pageId): void
    {
        // Rooms sit behind the Rooms section's password, a rule for visitors, not for this.
        $page = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($pageId));

        if (! $page instanceof Page || (string) $page->template()->name !== self::TEMPLATE || $this->trash->holds($pageId)) {
            $this->remove($pageId);

            return;
        }

        $room = Room::query()->firstOrCreate(['page_id' => $pageId]);
        $this->rewriteAvailability($room, $page);
    }

    public function remove(int $pageId): void
    {
        $room = Room::query()->where('page_id', $pageId)->first();

        if ($room instanceof Room) {
            $room->schedules()->each(fn ($schedule) => $schedule->delete());
            $room->delete();
        }
    }

    /** One availability schedule per group of weekdays that share hours, from today on. */
    private function rewriteAvailability(Room $room, Page $page): void
    {
        $room->availabilitySchedules()->each(fn ($schedule) => $schedule->delete());

        foreach (OpeningHours::of($page) as $group) {
            Zap::for($room)->named('Opening hours')->availability()
                ->from(today()->toDateString())
                ->addPeriod($group['opens'], $group['closes'])
                ->weekly($group['days'])
                ->save();
        }
    }
}
