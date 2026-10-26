<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Assistant\IssueAssistantToken;
use App\Actions\Assistant\RevokeAssistantToken;
use App\Actions\Bookings\BlockRoom;
use App\Actions\Bookings\BookRoom;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\ChangeBooking;
use App\Actions\Bookings\CheckIn;
use App\Actions\Bookings\EndEarly;
use App\Actions\Bookings\ReleaseNoShows;
use App\Actions\Calendar\IssueCalendarFeed;
use App\Actions\Calendar\RevokeCalendarFeed;
use App\Actions\Screens\IssueKioskLink;
use App\Actions\Screens\RevokeKioskLink;
use App\Agents\FindFreeRoomsTool;
use App\Agents\MyBookingsTool;
use App\Agents\RoomScheduleTool;
use App\Events\MeetingChanged;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\KioskController;
use App\Listeners\SendMeetingMails;
use App\Listeners\SyncRoomWithPage;
use App\Livewire\Admin\CalendarFeedTab;
use App\Livewire\Admin\KioskLinkTab;
use App\Livewire\Admin\RoomBookingsTab;
use App\Livewire\Admin\RoomsWeek;
use App\Livewire\AssistantAccess;
use App\Livewire\BookARoom;
use App\Livewire\MyBookings;
use App\View\RoomSchedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use LynxCms\Admin\Admin;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\EditorTabs;
use LynxCms\Admin\Navigation;
use LynxCms\Core\Actions\ActionRegistry;
use LynxCms\Core\Agents\ReadToolRegistry;
use LynxCms\Core\Events\PageDeleted;
use LynxCms\Core\Events\PageRestored;
use LynxCms\Core\Events\PageSaved;
use LynxCms\Core\Events\PageTemplateSwitched;
use LynxCms\Core\Events\PageTrashed;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/** The meeting rooms' wiring. Each phase adds its part here. */
final class RoomsServiceProvider extends ServiceProvider
{
    private const array ACTIONS = [
        BookRoom::class, ChangeBooking::class, CancelBooking::class, BlockRoom::class, CheckIn::class, EndEarly::class, ReleaseNoShows::class,
        IssueKioskLink::class, RevokeKioskLink::class, IssueCalendarFeed::class, RevokeCalendarFeed::class,
        IssueAssistantToken::class, RevokeAssistantToken::class,
    ];

    /** What assistants read over MCP; the actions above are their tools for writing. */
    private const array READ_TOOLS = [FindFreeRoomsTool::class, RoomScheduleTool::class, MyBookingsTool::class];

    public function boot(): void
    {
        lynx()->permissions()->register('rooms.book', 'Book meeting rooms, and change or cancel your own bookings');
        lynx()->permissions()->register('rooms.manage', 'Change or cancel anyone\'s bookings, block rooms, and link screens and room calendars');
        $this->app->make(ActionRegistry::class)->register(...self::ACTIONS);
        $this->app->make(ReadToolRegistry::class)->register(...self::READ_TOOLS);

        // The gap between meetings, for Zap's overlap check only: its free slots would shift the
        // edges of opening hours by it.
        config(['zap.conflict_detection.buffer_minutes' => Value::int(config('rooms.buffer_minutes'), 0)]);

        View::composer('templates.room', RoomSchedule::class);
        Event::listen([PageSaved::class, PageRestored::class, PageTemplateSwitched::class, PageTrashed::class, PageDeleted::class], SyncRoomWithPage::class);
        Event::listen(MeetingChanged::class, SendMeetingMails::class);

        // Screens open their kiosk link without a session: no cookie, no CSRF (the secret link is
        // the credential), nothing stored (no-store). Door screens post their two buttons.
        Route::prefix('screens/{token}')->where(['token' => '[A-Za-z0-9]+'])->middleware('throttle:120,1')->group(function (): void {
            Route::get('/', [KioskController::class, 'show'])->name('kiosk');
            Route::get('state', [KioskController::class, 'state'])->name('kiosk.state');
            Route::post('check-in', [KioskController::class, 'checkIn'])->name('kiosk.check-in');
            Route::post('end-early', [KioskController::class, 'endEarly'])->name('kiosk.end-early');
        });

        // Calendar apps subscribe to these from anywhere, without a session, like screens.
        Route::prefix('calendar')->where(['token' => '[A-Za-z0-9]+'])->middleware('throttle:60,1')->group(function (): void {
            Route::get('rooms/{token}.ics', [CalendarFeedController::class, 'room'])->name('calendar.room');
            Route::get('me/{token}.ics', [CalendarFeedController::class, 'me'])->name('calendar.me');
        });

        // The staff's booking page and their own bookings (routes/web.php).
        Livewire::component('book-a-room', BookARoom::class);
        Livewire::component('my-bookings', MyBookings::class);
        Livewire::component('assistant-access', AssistantAccess::class);

        $this->admin();
    }

    /** The admin's Bookings screen, the Bookings and Calendar tabs of rooms and the Kiosk link tab of screens. */
    private function admin(): void
    {
        Livewire::component('admin.rooms-week', RoomsWeek::class);
        Livewire::component('admin.room-bookings', RoomBookingsTab::class);
        Livewire::component('admin.kiosk-link', KioskLinkTab::class);
        Livewire::component('admin.calendar-feed', CalendarFeedTab::class);

        Admin::routes(function (): void {
            Admin::screen('bookings', 'admin.bookings', 'rooms.manage')->name('bookings');
        });

        $canManage = fn (): bool => app(AdminAccess::class)->allows('rooms.manage');
        app(Navigation::class)->add('Bookings', 'lynx.admin.bookings', order: 15, visible: $canManage, icon: 'calendar-days');
        app(EditorTabs::class)->add('bookings', 'Bookings', 'admin.room-bookings', fn (Page $page): bool => (string) $page->template()->name === 'room' && $canManage());
        app(EditorTabs::class)->add('calendar', 'Calendar feed', 'admin.calendar-feed', fn (Page $page): bool => (string) $page->template()->name === 'room' && $canManage());
        app(EditorTabs::class)->add('kiosk', 'Kiosk link', 'admin.kiosk-link', fn (Page $page): bool => (string) $page->template()->name === 'screen' && $canManage());

        // The admin ships its CSS built; the app's admin views get their own utilities.
        if (Vite::isRunningHot() || is_file(public_path('build/manifest.json'))) {
            config(['lynx-admin.stylesheets' => [...Value::strings(config('lynx-admin.stylesheets', [])), Vite::asset('resources/css/admin.css')]]);
        }
    }
}
