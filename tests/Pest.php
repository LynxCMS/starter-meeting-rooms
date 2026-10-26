<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoomsSchemaSeeder;
use Database\Seeders\SiteSchemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Actions\Roles\AssignRoles;
use LynxCms\Core\Actions\Runner\ActionResult;
use LynxCms\Core\Pages\Page;
use LynxCms\PagePassword\Access\Unlocks;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

// Every test runs on the morning of Monday 26 October 2026, so dates in tests stay in the future.
pest()->beforeEach(fn () => test()->travelTo(CarbonImmutable::parse('2026-10-26 08:00')))->in('Feature');

/** Lynx installed with the firm's schema (site and rooms): languages, templates, fields, roles. */
function roomsSchema(): void
{
    Artisan::call('lynx:install');
    (new SiteSchemaSeeder)->run();
    (new RoomsSchemaSeeder)->run();
}

/** The office page of that name, made on first use, in its time zone. */
function office(string $name = 'vancouver', string $zone = 'pacific'): Page
{
    $existing = lynx()->pages->get("/offices/{$name}/");

    if ($existing instanceof Page) {
        return $existing;
    }

    $page = lynx()->pages->new('office', '/offices/');
    $page->name = $name;
    $page->title = ucfirst($name);
    $page->city = ucfirst($name);
    $page->time_zone = $zone;

    return $page->save();
}

/**
 * A room page under /rooms/, open on the given days (weekday => [from, to]).
 *
 * @param  array<string, array{0: string, 1: string}>  $hours
 */
function roomPage(string $name, array $hours = ['monday' => ['09:00', '18:00']], int $capacity = 6, ?Page $office = null): Page
{
    $page = lynx()->pages->new('room', '/rooms/');
    $page->office = [($office ?? office())->id];
    $page->name = $name;
    $page->title = ucfirst($name);
    $page->capacity = $capacity;
    $page->opening_hours = openingHours($hours);

    return $page->save();
}

/**
 * Repeater items for opening hours.
 *
 * @param  array<string, array{0: string, 1: string}>  $hours
 * @return list<array{values: array<string, string>}>
 */
function openingHours(array $hours): array
{
    return array_values(array_map(
        fn (string $day, array $times): array => ['values' => ['weekday' => $day, 'opens' => $times[0], 'closes' => $times[1]]],
        array_keys($hours),
        $hours,
    ));
}

/**
 * An account with a role, acting as itself: "staff" books rooms, "manager" also changes anyone's
 * bookings and blocks rooms.
 */
function person(string $role, string $name = 'Ana'): Actor
{
    $user = User::factory()->create(['name' => $name]);
    lynx()->run(AssignRoles::class, ['user' => (string) $user->id, 'roles' => [$role]]);

    return Actor::user($user->id);
}

/**
 * Runs an action as the given actor.
 *
 * @param  class-string  $action
 * @param  array<string, mixed>  $input
 */
function runAs(Actor $actor, string $action, array $input, bool $dryRun = false): ActionResult
{
    return lynx()->actingAs($actor, fn (): ActionResult => lynx()->run($action, $input, dryRun: $dryRun));
}

/** Types the Rooms section's password in this test's browser, as staff do once every 30 days. */
function unlockRooms(mixed $test): void
{
    $rooms = lynx()->pages->get('/rooms/');
    $response = $test->post('/lynx-page-password/unlock', ['page' => $rooms?->id, 'password' => config('rooms.section_password'), 'language' => 'en']);
    // The test client keeps no cookies between requests; the browser would.
    $test->withCookie(Unlocks::COOKIE, (string) $response->getCookie(Unlocks::COOKIE)?->getValue());
}
