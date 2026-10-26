<?php

declare(strict_types=1);

use App\Actions\Bookings\BlockRoom;
use App\Bookings\Booking;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/*
 * rooms.block: a manager blocks a room for maintenance or an event; nobody books it then. A block
 * over existing meetings is refused, naming their times, so the manager cancels them first.
 */

beforeEach(function (): void {
    roomsSchema();
    roomPage('atlas', ['monday' => ['09:00', '18:00']]);
    $this->mia = person('manager', 'Mia');
});

/** @param array<string, mixed> $input */
function block(mixed $actor, array $input = []): Booking
{
    return runAs($actor, BlockRoom::class, $input + ['room' => '/rooms/atlas/', 'date' => '2026-11-02', 'from' => '13:00', 'to' => '15:00', 'reason' => 'New screen'])->valueAs(Booking::class);
}

it('blocks a room so nobody books it then', function (): void {
    $block = block($this->mia);

    expect($block->isBlocked)->toBeTrue()
        ->and($block->title)->toBe('New screen')
        ->and(fn (): Booking => book(person('staff', 'Ana'), ['from' => '14:00', 'to' => '14:30']))->toThrow(ValidationException::class, 'Atlas is taken from 13:00 to 15:00.');
});

it('blocks for longer than a meeting may last', function (): void {
    expect(block($this->mia, ['from' => '09:00', 'to' => '18:00'])->to)->toBe('18:00');
});

it('refuses a block over a meeting, naming its time', function (): void {
    book(person('staff', 'Ana'), ['from' => '14:00', 'to' => '14:30']);

    block($this->mia);
})->throws(ValidationException::class, 'Atlas is taken from 14:00 to 14:30.');

it('lets only managers block rooms', function (): void {
    block(person('staff', 'Ana'));
})->throws(AuthorizationException::class);
