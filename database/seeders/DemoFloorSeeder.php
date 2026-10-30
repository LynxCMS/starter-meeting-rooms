<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Bookings\BookRoom;
use App\Demo\DemoScreens;
use App\Events\MeetingChanged;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;

/**
 * For the public demo only: visitors arrive at any hour, and a real office's screens are dark at
 * night and on weekends. So every room stays open and needs no check in (a meeting nobody checks
 * into would vanish after ten minutes), and each reset books meetings around that moment. Half
 * the rooms are busy and half are free, with something coming in each.
 */
final class DemoFloorSeeder extends Seeder
{
    private const array WEEK = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /** Minutes from now that each meeting starts and ends, for a busy room and for a free one. */
    private const array BUSY = [[0, 50], [55, 85]];

    private const array FREE = [[20, 50], [55, 85]];

    private const array TITLES = [
        'Beaver dam permit: hearing prep', 'Otter custody mediation', 'Heron nesting rights', 'Muskrat easement review',
        'Salmon run: expert witness', 'Loon noise complaint', 'Moose right of way', 'Trout v. Upstream Mill, Inc.',
    ];

    public function run(): void
    {
        $rooms = app(PageVisibility::class)->withoutRules(fn () => lynx()->pages->find('template=room')->all());
        $organizer = Actor::user((int) User::query()->where('email', 'ana@meeting-rooms.example')->value('id'));

        Event::fakeFor(function () use ($rooms, $organizer): void {
            foreach ($rooms as $index => $page) {
                $this->open($page);
                lynx()->actingAs($organizer, fn () => $this->book($page, $index));
            }
        }, [MeetingChanged::class]);

        DemoScreens::all();
    }

    private function open(Page $room): void
    {
        $room->check_in = false;
        $room->opening_hours = array_map(fn (string $day): array => ['values' => ['weekday' => $day, 'opens' => '00:00', 'closes' => '23:59']], self::WEEK);
        $room->save();
    }

    /** Close to midnight a meeting that would run into the next day is left out. */
    private function book(Page $page, int $index): void
    {
        $zone = Room::forPage($page)?->timeZone();
        $start = CarbonImmutable::now($zone)->addMinutes(2);

        foreach ($index % 2 === 0 ? self::BUSY : self::FREE as $meeting => [$from, $to]) {
            if ($start->addMinutes($to)->isSameDay($start)) {
                lynx()->run(BookRoom::class, [
                    'room' => (string) $page->id,
                    'date' => $start->toDateString(),
                    'from' => $start->addMinutes($from)->format('H:i'),
                    'to' => $start->addMinutes($to)->format('H:i'),
                    'title' => self::TITLES[($index * 2 + $meeting) % count(self::TITLES)],
                ]);
            }
        }
    }
}
