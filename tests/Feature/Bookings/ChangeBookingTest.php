<?php

declare(strict_types=1);

use App\Actions\Bookings\ChangeBooking;
use App\Bookings\Booking;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\ActionHalted;

/*
 * rooms.change: the organizer, or a manager, moves a booking to another time, day or room, or
 * renames it. The same rules as booking apply, and the booking never conflicts with itself.
 */

beforeEach(function (): void {
    roomsSchema();
    roomPage('atlas', ['monday' => ['09:00', '18:00']]);
    roomPage('orion', ['monday' => ['09:00', '18:00']]);
    $this->ana = person('staff', 'Ana');
    $this->booking = book($this->ana);
});

/** @param array<string, mixed> $input */
function change(mixed $actor, int $booking, array $input): Booking
{
    return runAs($actor, ChangeBooking::class, ['booking' => $booking] + $input)->valueAs(Booking::class);
}

it('moves a booking later, overlapping only itself', function (): void {
    $moved = change($this->ana, $this->booking->id, ['from' => '10:30', 'to' => '11:30', 'title' => 'Planning, part two']);

    expect([$moved->id, $moved->from, $moved->to, $moved->title])->toBe([$this->booking->id, '10:30', '11:30', 'Planning, part two']);
});

it('moves a booking to another room', function (): void {
    expect(change($this->ana, $this->booking->id, ['room' => '/rooms/orion/'])->room()?->title)->toEqual('Orion');
});

it('applies the booking rules to the new time', function (): void {
    book(person('staff', 'Bo'), ['from' => '12:00', 'to' => '13:00']);

    expect(fn (): Booking => change($this->ana, $this->booking->id, ['from' => '11:30', 'to' => '12:30']))->toThrow(ValidationException::class, 'Atlas is taken from 12:00 to 13:00.')
        ->and(fn (): Booking => change($this->ana, $this->booking->id, ['from' => '08:00', 'to' => '09:00']))->toThrow(ValidationException::class, 'Atlas is open from 09:00 to 18:00 on Mondays.');
});

it('lets only the organizer or a manager change a booking', function (): void {
    expect(fn (): Booking => change(person('staff', 'Bo'), $this->booking->id, ['title' => 'Mine now']))->toThrow(AuthorizationException::class)
        ->and(change(person('manager', 'Mia'), $this->booking->id, ['title' => 'Moved by the office'])->title)->toBe('Moved by the office');
});

it('refuses a booking that does not exist', function (): void {
    change($this->ana, 999, ['title' => 'Nothing']);
})->throws(ValidationException::class, 'There is no booking [999].');

it('changes nothing on a dry run, and nothing when the action rolls back', function (): void {
    runAs($this->ana, ChangeBooking::class, ['booking' => $this->booking->id, 'from' => '15:00', 'to' => '16:00'], dryRun: true);
    expect(Booking::find($this->booking->id)?->from)->toBe('10:00');

    lynx()->hook(ChangeBooking::class)->after(fn () => throw new ActionHalted('No.'));
    expect(fn (): Booking => change($this->ana, $this->booking->id, ['from' => '15:00', 'to' => '16:00']))->toThrow(ActionHalted::class)
        ->and(Booking::find($this->booking->id)?->from)->toBe('10:00');
});
