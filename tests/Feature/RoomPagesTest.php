<?php

declare(strict_types=1);

use App\Models\Room;
use App\Rooms\FreeTimes;
use Database\Seeders\DatabaseSeeder;

/* The public pages: the rooms, and each room's hours and free times. */

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    unlockRooms($this);
});

it('lists the rooms by office', function (): void {
    $this->get('/rooms')->assertOk()->assertSeeInOrder(['Vancouver', 'Kingfisher', 'The Lodge', 'Montréal', 'Huard', 'Saint-Laurent']);
});

it('shows a room\'s opening hours and when it is free', function (): void {
    $this->get('/rooms/saint-laurent')->assertOk();

    expect(FreeTimes::on(roomAt('/rooms/saint-laurent/'), '2026-10-26'))->toBe([['from' => '09:00', 'to' => '18:00']]);
});

it('has no free time on a day the room is open but fully booked', function (): void {
    Zap\Facades\Zap::for(roomAt('/rooms/huard/'))->named('Offsite')->appointment()->from('2026-10-26')->addPeriod('08:30', '17:30')->save();

    $this->get('/rooms/huard')->assertOk();
    expect(FreeTimes::on(roomAt('/rooms/huard/'), '2026-10-26'))->toBe([]);
});

it('shows each day\'s meetings, a private one without its title, and blocked times', function (): void {
    // The demo's meetings are on the next working day: Tuesday 27 October.
    $this->get('/rooms/lodge')->assertOk()->assertSee('Weekly planning');
    $this->get('/rooms/kingfisher')->assertOk()->assertDontSee('Salary review');
    $this->get('/rooms/saint-laurent')->assertOk()->assertSee('New whiteboard');

    expect(FreeTimes::on(roomAt('/rooms/lodge/'), '2026-10-27'))->toBe([['from' => '08:00', 'to' => '10:00'], ['from' => '11:00', 'to' => '15:00'], ['from' => '16:00', 'to' => '20:00']]);
});

function roomAt(string $path): Room
{
    return Room::forPage(lynx()->pages->get($path) ?? throw new RuntimeException("No room at {$path}.")) ?? throw new RuntimeException("No room row for {$path}.");
}
