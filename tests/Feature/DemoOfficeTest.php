<?php

declare(strict_types=1);

use App\Models\Room;
use Database\Seeders\DatabaseSeeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;

/* The seeded firm: two offices, two rooms each with their hours, a lobby per office and a door screen per room. */

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('seeds four rooms, each with a room for Zap', function (): void {
    expect(Room::query()->count())->toBe(4)
        ->and(app(PageVisibility::class)->withoutRules(fn (): int => lynx()->pages->find('template=room')->count()))->toBe(4);
});

it('follows each room\'s hours: Saint-Laurent closes early on Fridays', function (): void {
    $room = Room::forPage(lynx()->pages->get('/rooms/saint-laurent/') ?? throw new RuntimeException('No Saint-Laurent.'));

    expect($room?->isBookableAtTime('2026-11-06', '12:00', '13:00'))->toBeTrue()
        ->and($room?->isBookableAtTime('2026-11-06', '14:00', '15:00'))->toBeFalse()
        ->and($room?->isBookableAtTime('2026-11-07', '10:00', '11:00'))->toBeFalse();
});

it('seeds a lobby screen per office and a door screen per room', function (): void {
    $screens = lynx()->pages->find('template=screen, include=hidden');

    expect($screens->count())->toBe(6)
        ->and($screens->filter(fn (Page $screen): bool => $screen->kind?->value === 'door')->count())->toBe(4);
});
