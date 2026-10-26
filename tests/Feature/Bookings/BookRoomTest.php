<?php

declare(strict_types=1);

use App\Actions\Bookings\BookRoom;
use App\Bookings\Booking;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\ActionHalted;
use LynxCms\Contracts\Actions\Actor;
use Zap\Models\Schedule;

/*
 * rooms.book: a staff member books a room for a period on a day. The room must be open then,
 * the meeting between 15 minutes and 4 hours, not in the past, and clear of other meetings and
 * blocked times by 5 minutes.
 */

beforeEach(function (): void {
    roomsSchema();
    roomPage('atlas', ['monday' => ['09:00', '18:00'], 'tuesday' => ['09:00', '18:00']]);
    $this->ana = person('staff', 'Ana');
});

/** @param array<string, mixed> $input */
function book(Actor $actor, array $input = []): Booking
{
    return runAs($actor, BookRoom::class, $input + ['room' => '/rooms/atlas/', 'date' => '2026-11-02', 'from' => '10:00', 'to' => '11:00', 'title' => 'Planning'])->valueAs(Booking::class);
}

it('books a room for its organizer', function (): void {
    $booking = book($this->ana);

    expect($booking->title)->toBe('Planning')
        ->and($booking->date)->toBe('2026-11-02')
        ->and([$booking->from, $booking->to])->toBe(['10:00', '11:00'])
        ->and($booking->organizerId)->toBe($this->ana->id)
        ->and($booking->isPrivate)->toBeFalse()
        ->and($booking->room()?->title)->toEqual('Atlas');
});

it('keeps a private booking\'s title out of what others read', function (): void {
    $booking = book($this->ana, ['private' => true, 'title' => 'Salary review']);

    expect($booking->isPrivate)->toBeTrue()
        ->and($booking->publicTitle())->toBe('Busy')
        ->and($booking->title)->toBe('Salary review');
});

it('refuses times outside the room\'s opening hours', function (array $input, string $message): void {
    expect(fn (): Booking => book($this->ana, $input))->toThrow(ValidationException::class, $message);
})->with([
    'before opening' => [['from' => '08:00', 'to' => '09:30'], 'Atlas is open from 09:00 to 18:00 on Mondays.'],
    'after closing' => [['from' => '17:30', 'to' => '18:30'], 'Atlas is open from 09:00 to 18:00 on Mondays.'],
    'a closed day' => [['date' => '2026-11-04'], 'Atlas is closed on Wednesdays.'],
]);

it('refuses meetings too short, too long, backwards or in the past', function (array $input, string $field): void {
    expect(fn (): Booking => book($this->ana, $input))->toThrow(ValidationException::class);

    try {
        book($this->ana, $input);
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }
})->with([
    'ten minutes' => [['from' => '10:00', 'to' => '10:10'], 'to'],
    'five hours' => [['from' => '09:00', 'to' => '14:00'], 'to'],
    'ends before it starts' => [['from' => '11:00', 'to' => '10:00'], 'to'],
    'already over' => [['date' => '2026-10-19'], 'date'],
    'not a time' => [['from' => '25:00'], 'from'],
]);

it('refuses an overlap without telling whose meeting it is', function (): void {
    book($this->ana, ['title' => 'Secret project']);

    expect(fn (): Booking => book(person('staff', 'Bo'), ['from' => '10:30', 'to' => '11:30']))
        ->toThrow(ValidationException::class, 'Atlas is taken from 10:00 to 11:00.');

    try {
        book(person('staff', 'Cy'), ['from' => '10:30', 'to' => '11:30']);
    } catch (ValidationException $exception) {
        expect(json_encode($exception->errors()))->not->toContain('Secret project');
    }
});

it('keeps 5 minutes between meetings', function (): void {
    book($this->ana);

    expect(fn (): Booking => book($this->ana, ['from' => '11:02', 'to' => '12:00']))->toThrow(ValidationException::class, '5 minutes between meetings')
        ->and(book($this->ana, ['from' => '11:05', 'to' => '12:00'])->from)->toBe('11:05');
});

it('lets only people with rooms.book book', function (): void {
    book(person('guest', 'Gus'));
})->throws(AuthorizationException::class);

it('books for someone else only for managers', function (): void {
    $bo = person('staff', 'Bo');

    expect(book($this->ana, ['organizer' => $bo->id])->organizerId)->toBe($this->ana->id)
        ->and(book(person('manager', 'Mia'), ['organizer' => $bo->id, 'from' => '14:00', 'to' => '15:00'])->organizerId)->toBe($bo->id);
});

it('writes nothing on a dry run, and nothing when the action rolls back', function (): void {
    $result = runAs($this->ana, BookRoom::class, ['room' => '/rooms/atlas/', 'date' => '2026-11-02', 'from' => '10:00', 'to' => '11:00', 'title' => 'Planning'], dryRun: true);
    expect(implode("\n", $result->changes->summary()))->toContain('Book Atlas on Monday 2 November 2026 from 10:00 to 11:00: Planning')
        ->and(Schedule::query()->where('schedule_type', 'appointment')->count())->toBe(0);

    lynx()->hook(BookRoom::class)->after(fn () => throw new ActionHalted('No.'));
    expect(fn (): Booking => book($this->ana))->toThrow(ActionHalted::class)
        ->and(Schedule::query()->where('schedule_type', 'appointment')->count())->toBe(0);
});
