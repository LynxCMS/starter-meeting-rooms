<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Actions\Bookings\BlockRoom;
use App\Actions\Bookings\BookRoom;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\ChangeBooking;
use App\Bookings\Booking;
use App\Livewire\Concerns\InvitesPeople;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\AdminServiceProvider;

/**
 * The booking form the admin's booking screens share: book a room, block it, or change or cancel
 * a booking. Every save runs a Lynx action as the signed-in manager, so the office's rules, Zap's
 * conflict check, permissions and the activity log apply as they do everywhere else.
 */
trait EditsBookings
{
    use InvitesPeople;

    /**
     * The form open, if any. repeat and until make a new booking a series; occurrence, repeats and
     * scope say, for a booking of a series, which date it is and whether a change is for that date
     * or the whole series. series says it belongs to one; organizer is who booked it, the only one
     * who says who is invited (attendees, from InvitesPeople); opened is its time as opened, so a
     * change to a whole series leaves each date's own time unless the time itself was changed.
     *
     * @var array{mode: string, booking: int|null, room: int, date: string, from: string, to: string, title: string, private: bool, repeat: string, until: string, occurrence: string|null, repeats: string|null, series: bool, organizer: string|null, opened: string, scope: string}|null
     */
    public ?array $form = null;

    public ?string $notice = null;

    /**
     * Livewire answers clicks at its own endpoint, outside the admin's routes, so access is checked
     * on every request: the admin's gate, then the right to manage rooms.
     */
    public function bootEditsBookings(): void
    {
        Gate::authorize(AdminServiceProvider::GATE);
        abort_unless(app(AdminAccess::class)->allows('rooms.manage'), 403);
    }

    public function startBooking(int $room, string $date): void
    {
        $this->open('book', $room, $date, '09:00', '10:00');
    }

    public function startBlocking(int $room, string $date): void
    {
        $this->open('block', $room, $date, '09:00', '17:00');
    }

    /** Opens a booking as it is on $date, the day clicked (for a series, that date's meeting). */
    public function editBooking(int $id, string $date): void
    {
        $booking = Booking::find($id, $date);
        abort_if($booking === null, 404);

        $this->open($booking->isBlocked ? 'block' : 'edit', (int) $booking->room()?->id, $booking->date, $booking->from, $booking->to, $booking);
    }

    /**
     * Whether the form may say who is invited: the signed-in manager's own meeting, as a whole
     * (a booking of its own, or a whole series).
     */
    public function mayInvite(): bool
    {
        $form = $this->form;

        return $form !== null && $form['mode'] !== 'block' && $form['organizer'] === (string) auth()->id()
            && (! $form['series'] || $form['scope'] === 'series');
    }

    /**
     * The picker's people, and the names of those invited, for the form's view.
     *
     * @return array{invitable: list<User>, invited: list<User>}
     */
    private function formPeople(): array
    {
        return $this->people($this->form['organizer'] ?? auth()->id());
    }

    public function closeForm(): void
    {
        $this->form = null;
        $this->resetErrorBag();
    }

    public function saveForm(): void
    {
        if ($this->form === null) {
            return;
        }

        $form = $this->form;
        $period = ['date' => $form['date'], 'from' => $form['from'], 'to' => $form['to']];

        $this->attempt(fn () => match (true) {
            $form['booking'] !== null && $form['mode'] === 'block' => $this->replaceBlock($form, $period),
            $form['booking'] !== null => lynx()->run(ChangeBooking::class, $this->changeInput($form, $period)),
            $form['mode'] === 'block' => lynx()->run(BlockRoom::class, ['room' => $form['room'], ...$period, 'reason' => $form['title']]),
            default => lynx()->run(BookRoom::class, ['room' => $form['room'], ...$period, 'title' => $form['title'], 'private' => $form['private'], 'attendees' => $this->attendees, ...$this->repeatInput($form)]),
        }, $form['mode'] === 'block' ? 'Blocked.' : 'Saved.');
    }

    /** Cancels the booking open in the form: that date of a series, or the whole series. */
    public function cancelBooking(): void
    {
        $form = $this->form;

        if ($form !== null && $form['booking'] !== null) {
            $this->attempt(fn () => lynx()->run(CancelBooking::class, ['booking' => $form['booking'], 'occurrence' => $form['occurrence'], 'scope' => $form['scope']]), 'Cancelled.');
        }
    }

    /**
     * A change: for a whole series, only its time (when changed), title, privacy and attendees (it
     * keeps its days and room).
     *
     * @param  array{booking: int|null, room: int, title: string, private: bool, occurrence: string|null, opened: string, scope: string}  $form
     * @param  array{date: string, from: string, to: string}  $period
     * @return array<string, mixed>
     */
    private function changeInput(array $form, array $period): array
    {
        $isSeries = $form['scope'] === 'series';
        $input = ['booking' => $form['booking'], 'title' => $form['title'], 'private' => $form['private'], 'occurrence' => $form['occurrence'], 'scope' => $form['scope']]
            + ($isSeries && $form['opened'] === $period['from'].'-'.$period['to'] ? [] : ['from' => $period['from'], 'to' => $period['to']])
            + ($this->mayInvite() ? ['attendees' => $this->attendees] : []);

        return $isSeries ? $input : $input + ['room' => $form['room'], 'date' => $period['date']];
    }

    /**
     * @param  array{repeat: string, until: string}  $form
     * @return array<string, string>
     */
    private function repeatInput(array $form): array
    {
        return $form['repeat'] === '' ? [] : ['repeat' => $form['repeat'], 'until' => $form['until']];
    }

    /** Opens the form for a new booking or blocked time, or for $booking as it is on $date. */
    private function open(string $mode, int $room, string $date, string $from, string $to, ?Booking $booking = null): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $this->form = [
            'mode' => $mode, 'booking' => $booking?->id, 'room' => $room, 'date' => $date, 'from' => $from, 'to' => $to, 'opened' => $from.'-'.$to,
            'title' => $booking->title ?? '', 'private' => $booking->isPrivate ?? false,
            'repeat' => '', 'until' => CarbonImmutable::parse($date)->addMonths(3)->toDateString(),
            'occurrence' => $booking?->repeats === null ? null : $date, 'repeats' => $booking?->repeats, 'series' => $booking?->series !== null,
            'organizer' => $booking === null ? (string) auth()->id() : ($booking->organizerId === null ? null : (string) $booking->organizerId), 'scope' => 'one',
        ];
        $this->inviteIds($booking->attendees ?? []);
    }

    /**
     * Blocked times have no change action: one is lifted and blocked again, in one go, so a
     * refused new time leaves the old one in place.
     *
     * @param  array{booking: int|null, room: int, title: string, occurrence: string|null, scope: string}  $form
     * @param  array{date: string, from: string, to: string}  $period
     */
    private function replaceBlock(array $form, array $period): mixed
    {
        return DB::transaction(function () use ($form, $period): mixed {
            lynx()->run(CancelBooking::class, ['booking' => $form['booking'], 'occurrence' => $form['occurrence'], 'scope' => $form['scope']]);

            return lynx()->run(BlockRoom::class, ['room' => $form['room'], ...$period, 'reason' => $form['title']]);
        });
    }

    /** Runs a save, showing a refusal on the form instead of failing the request. */
    private function attempt(callable $save, string $notice): void
    {
        $this->resetErrorBag();

        try {
            $save();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                // Blocked times call their title a reason.
                $this->addError('form.'.($field === 'reason' ? 'title' : $field), $messages[0] ?? 'Not valid.');
            }

            return;
        } catch (AuthorizationException $exception) {
            $this->addError('form.title', $exception->getMessage());

            return;
        }

        $this->form = null;
        $this->notice = $notice;
    }
}
