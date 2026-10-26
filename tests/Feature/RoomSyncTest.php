<?php

declare(strict_types=1);

use App\Models\Room;
use App\Rooms\FreeTimes;
use Zap\Models\Schedule;

/*
 * Rooms are Lynx pages; Zap needs an Eloquent model to hang schedules on. Saving a room page
 * keeps its Room in step, and its opening hours become the room's availability, so editors only
 * ever edit the page.
 */

beforeEach(fn () => roomsSchema());

it('gives a new room page a room, available in its opening hours', function (): void {
    $page = roomPage('atlas', ['monday' => ['09:00', '18:00'], 'tuesday' => ['09:00', '18:00']]);
    $room = Room::forPage($page);

    expect($room)->not->toBeNull()
        ->and($room?->isBookableAtTime('2026-11-02', '09:00', '10:00'))->toBeTrue()   // a Monday
        ->and($room?->isBookableAtTime('2026-11-03', '17:00', '18:00'))->toBeTrue()   // a Tuesday
        ->and($room?->isBookableAtTime('2026-11-02', '08:00', '09:00'))->toBeFalse()
        ->and($room?->isBookableAtTime('2026-11-04', '10:00', '11:00'))->toBeFalse(); // a Wednesday
});

it('follows the page when its opening hours change', function (): void {
    $page = roomPage('atlas', ['monday' => ['09:00', '18:00']]);
    $page->opening_hours = openingHours(['wednesday' => ['14:00', '16:00']]);
    $page->save();
    $room = Room::forPage($page);

    expect($room?->isBookableAtTime('2026-11-02', '09:00', '10:00'))->toBeFalse()
        ->and($room?->isBookableAtTime('2026-11-04', '14:00', '15:00'))->toBeTrue()
        ->and($room?->availabilitySchedules()->count())->toBe(1);
});

it('keeps different hours on different days apart', function (): void {
    $room = Room::forPage(roomPage('atlas', ['monday' => ['09:00', '18:00'], 'friday' => ['09:00', '13:00']]));

    expect($room?->isBookableAtTime('2026-11-06', '12:00', '13:00'))->toBeTrue()
        ->and($room?->isBookableAtTime('2026-11-06', '14:00', '15:00'))->toBeFalse()
        ->and($room?->isBookableAtTime('2026-11-02', '14:00', '15:00'))->toBeTrue();
});

it('is never bookable without opening hours', function (): void {
    $room = Room::forPage(roomPage('closet', []));

    expect($room)->not->toBeNull()
        ->and($room?->isBookableAt('2026-11-02', 30))->toBeFalse();
});

it('removes the room with its schedules when its page goes to the trash, and brings it back on restore', function (): void {
    $page = roomPage('atlas');
    lynx()->pages->trash($page);

    expect(Room::forPage($page))->toBeNull()
        ->and(Schedule::query()->count())->toBe(0);

    lynx()->pages->restore($page);

    expect(Room::forPage($page)?->isBookableAtTime('2026-11-02', '09:00', '10:00'))->toBeTrue();
});

it('gives pages of other templates no room', function (): void {
    $announcement = lynx()->pages->new('announcement', lynx()->pages->find('template=announcements, include=hidden')->first());
    $announcement->title = 'Fire drill on Friday';
    $announcement->save();

    expect(Room::query()->count())->toBe(0);
});

it('reads the room\'s content from its page', function (): void {
    $page = roomPage('atlas', capacity: 8);

    expect(Room::forPage($page)?->page()?->capacity)->toBe(8);
});

it('says when a room is free on a day, in ranges', function (): void {
    $room = Room::forPage(roomPage('atlas', ['monday' => ['09:00', '18:00']]));
    Zap\Facades\Zap::for($room)->named('Planning')->appointment()->from('2026-11-02')->addPeriod('10:00', '11:30')->save();

    expect(FreeTimes::on($room, '2026-11-02'))->toBe([['from' => '09:00', 'to' => '10:00'], ['from' => '11:30', 'to' => '18:00']])
        ->and(FreeTimes::on($room, '2026-11-03'))->toBe([]);
});
