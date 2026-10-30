<?php

declare(strict_types=1);

use App\Models\KioskLink;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoFloorSeeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/*
 * The public demo's screens: each has a link that outlives the hourly reset, listed on a page
 * anyone can open, and they show something at any hour, wherever the visitor is.
 */

beforeEach(fn () => seedDemo());

/** The addresses the demo's screens page links to. */
function demoScreenLinks(): array
{
    preg_match_all('/data-demo-screen="([^"]*)"/', (string) test()->get(route('demo.screens'))->assertOk()->getContent(), $matches);

    return $matches[1];
}

it('lists every screen, and each link opens it', function (): void {
    config(['rooms.demo_reset' => true]);

    $links = demoScreenLinks();

    expect($links)->toHaveCount(6);

    foreach ($links as $link) {
        $this->get($link)->assertOk();
    }
});

it('keeps the same links, so a screen left open survives the reset', function (): void {
    config(['rooms.demo_reset' => true]);

    $links = demoScreenLinks();
    KioskLink::query()->delete();

    expect(demoScreenLinks())->toBe($links);
});

it('gives a screen its link back when a visitor revoked it', function (): void {
    config(['rooms.demo_reset' => true]);
    [$link] = demoScreenLinks();
    KioskLink::query()->delete();

    demoScreenLinks();

    $this->get($link)->assertOk();
});

it('shows no screens on other sites', function (): void {
    config(['rooms.demo_reset' => false]);

    $this->get(route('demo.screens'))->assertNotFound();
});

it('links to the screens from the sign-in page and the site, on the demo only', function (bool $isDemo): void {
    config(['rooms.demo_reset' => $isDemo]);

    foreach ([route('login'), '/'] as $address) {
        expect(str_contains((string) $this->get($address)->getContent(), route('demo.screens')))->toBe($isDemo);
    }
})->with(['on the demo' => true, 'on other sites' => false]);

it('opens the rooms round the clock on the demo, with a meeting running and one coming', function (string $moment): void {
    $this->travelTo(CarbonImmutable::parse($moment));
    config(['rooms.demo_reset' => true]);
    $this->seed(DemoFloorSeeder::class);
    $this->travel(5)->minutes();

    $lobbies = array_filter(
        array_map(fn (string $link): array => test()->get("{$link}/state")->assertOk()->json(), demoScreenLinks()),
        fn (array $state): bool => $state['kind'] === 'lobby',
    );

    expect($lobbies)->toHaveCount(2);

    foreach ($lobbies as $lobby) {
        $now = sprintf('%02d:%02d', intdiv($lobby['minute'], 60), $lobby['minute'] % 60);
        $meetings = array_merge(...array_column($lobby['rooms'], 'meetings'));
        $running = array_filter($meetings, fn (array $meeting): bool => $meeting['from'] <= $now && $now < $meeting['to']);
        $coming = array_filter($meetings, fn (array $meeting): bool => $meeting['from'] > $now);

        expect($running)->not->toBe([])
            ->and($coming)->not->toBe([]);
    }
})->with([
    'in the office\'s morning' => ['2026-10-26 17:00'],
    'in the middle of its night' => ['2026-10-27 09:00'],
    'on a Sunday' => ['2026-11-01 16:00'],
]);

it('leaves the rooms as they are on other sites', function (): void {
    $lodge = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get('/rooms/lodge/'));

    expect(Value::bool($lodge?->check_in))->toBeTrue();
});
