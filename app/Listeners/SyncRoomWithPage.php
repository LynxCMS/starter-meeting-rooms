<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Rooms\RoomSync;
use LynxCms\Core\Events\PageDeleted;
use LynxCms\Core\Events\PageRestored;
use LynxCms\Core\Events\PageSaved;
use LynxCms\Core\Events\PageTemplateSwitched;
use LynxCms\Core\Events\PageTrashed;

/** Lynx's page events, after their action commits, passed on to RoomSync. */
final readonly class SyncRoomWithPage
{
    public function __construct(private RoomSync $rooms) {}

    public function handle(PageSaved|PageRestored|PageTemplateSwitched|PageTrashed|PageDeleted $event): void
    {
        $removed = match (true) {
            $event instanceof PageTrashed, $event instanceof PageDeleted => $event->pageIds,
            default => [],
        };

        foreach ($removed as $pageId) {
            $this->rooms->remove($pageId);
        }

        if ($event instanceof PageSaved || $event instanceof PageRestored || $event instanceof PageTemplateSwitched) {
            $this->rooms->sync($event->pageId);
        }
    }
}
