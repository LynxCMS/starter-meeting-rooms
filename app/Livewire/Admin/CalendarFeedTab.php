<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Calendar\IssueCalendarFeed;
use App\Actions\Calendar\RevokeCalendarFeed;
use App\Models\CalendarFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\AdminServiceProvider;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * The Calendar feed tab of a room's page editor: whether the room has a feed address, issuing a
 * new one (shown this once) and revoking it.
 */
final class CalendarFeedTab extends Component
{
    public int $pageId;

    /** The address just issued; it lives only in this component's state until the tab is left. */
    public ?string $issued = null;

    public function boot(): void
    {
        Gate::authorize(AdminServiceProvider::GATE);
        abort_unless(app(AdminAccess::class)->allows('rooms.manage'), 403);
    }

    public function issue(): void
    {
        $this->issued = Value::string(lynx()->run(IssueCalendarFeed::class, ['room' => $this->pageId])->value);
    }

    public function revoke(): void
    {
        lynx()->run(RevokeCalendarFeed::class, ['room' => $this->pageId]);
        $this->issued = null;
    }

    public function render(): View
    {
        $room = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($this->pageId));
        abort_unless($room instanceof Page && (string) $room->template()->name === 'room', 404);

        return view('livewire.admin.calendar-feed', ['room' => $room, 'feed' => CalendarFeed::forRoom($this->pageId)]);
    }
}
