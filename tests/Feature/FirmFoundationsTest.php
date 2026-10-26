<?php

declare(strict_types=1);

use App\Actions\Bookings\BookRoom;
use App\Bookings\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\GenericUser;
use Illuminate\Validation\ValidationException;
use LynxCms\PagePassword\PagePasswordSchema;

/*
 * Castor & Otters, Inc.: the site in English and French, two offices in their own time zones,
 * and the meeting rooms behind one password on the Rooms section.
 */

beforeEach(function (): void {
    $this->withoutVite();
    seedDemo();
});

it('speaks English and Canadian French', function (): void {
    $fr = lynx()->languages->find('fr');

    expect(lynx()->languages->default()->code)->toBe('en')
        ->and($fr?->locale)->toBe('fr-CA')
        ->and($fr?->fallback?->code)->toBe('en');

    $this->get('/')->assertOk()->assertSee('lang="en-CA"', false);
    $this->get('/fr')->assertOk()->assertSee('lang="fr-CA"', false);
});

it('has an office in Vancouver and one in Montreal', function (): void {
    $this->get('/offices/vancouver')->assertOk();
    $this->get('/fr/bureaux/montreal')->assertOk();
});

it('gives each room its office\'s time zone', function (): void {
    expect(Room::forPage(lynx()->pages->get('/rooms/lodge/'))?->timeZone())->toBe('America/Vancouver')
        ->and(Room::forPage(lynx()->pages->get('/rooms/saint-laurent/'))?->timeZone())->toBe('America/Toronto');
});

it('says "already passed" in the room\'s own time zone', function (): void {
    // 16:30 UTC on Monday 26 October: 09:30 in Vancouver, 12:30 in Montreal.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 16:30', 'UTC'));
    $mia = person('manager', 'Mia');
    $book = fn (string $room) => runAs($mia, BookRoom::class, ['room' => $room, 'date' => '2026-10-26', 'from' => '11:00', 'to' => '12:00', 'title' => 'Late morning'])->valueAs(Booking::class);

    expect($book('/rooms/lodge/')->from)->toBe('11:00')
        ->and(fn () => $book('/rooms/saint-laurent/'))->toThrow(ValidationException::class, 'That time has already passed.');
});

it('keeps the rooms behind the Rooms section\'s password', function (): void {
    $rooms = lynx()->pages->get('/rooms/');

    expect($rooms?->get(PagePasswordSchema::FIELD)?->matches((string) config('rooms.section_password')))->toBeTrue();

    $this->get('/rooms')->assertForbidden()->assertSee('name="password"', false);
    $this->get('/rooms/lodge')->assertForbidden();
    $this->get('/fr/salles')->assertForbidden();
});

it('opens the rooms with the password, for 30 days in that browser', function (): void {
    unlockRooms($this);

    $this->get('/rooms')->assertOk();
    $this->get('/rooms/lodge')->assertOk();
});

it('lets office managers in without the password', function (): void {
    $mia = User::query()->where('email', 'mia@meeting-rooms.example')->firstOrFail();

    $this->actingAs(new GenericUser(['id' => $mia->id]))->get('/rooms')->assertOk();
});

it('has the site\'s sections, in both languages', function (string $en, string $fr): void {
    $this->get($en)->assertOk();
    $this->get($fr)->assertOk();
})->with([
    'practice areas' => ['/practice-areas', '/fr/domaines'],
    'team' => ['/team', '/fr/equipe'],
    'news' => ['/news', '/fr/nouvelles'],
    'contact' => ['/contact', '/fr/contact'],
]);

it('asks for the password in the visitor\'s language', function (): void {
    $this->get('/fr/salles')->assertForbidden()->assertSee('lang="fr-CA"', false)->assertSee('name="password"', false);
});
