<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Bookings\Booking;
use Illuminate\Auth\Access\AuthorizationException;

/* rooms.cancel: the organizer, or a manager, cancels a booking; its time is free again. */

beforeEach(function (): void {
    roomsSchema();
    roomPage('atlas', ['monday' => ['09:00', '18:00']]);
    $this->ana = person('staff', 'Ana');
    $this->booking = book($this->ana);
});

it('frees the time when the organizer cancels', function (): void {
    runAs($this->ana, CancelBooking::class, ['booking' => $this->booking->id]);

    expect(Booking::find($this->booking->id))->toBeNull()
        ->and(book(person('staff', 'Bo'))->from)->toBe('10:00');
});

it('lets only the organizer or a manager cancel', function (): void {
    expect(fn () => runAs(person('staff', 'Bo'), CancelBooking::class, ['booking' => $this->booking->id]))->toThrow(AuthorizationException::class);

    runAs(person('manager', 'Mia'), CancelBooking::class, ['booking' => $this->booking->id]);
    expect(Booking::find($this->booking->id))->toBeNull();
});

it('marks cancelling as destructive and keeps the booking on a dry run', function (): void {
    $result = runAs($this->ana, CancelBooking::class, ['booking' => $this->booking->id], dryRun: true);

    expect(CancelBooking::destructive())->toBeTrue()
        ->and(implode("\n", $result->changes->summary()))->toContain('Cancel Planning in Atlas on Monday 2 November 2026 from 10:00 to 11:00')
        ->and(Booking::find($this->booking->id))->not->toBeNull();
});
