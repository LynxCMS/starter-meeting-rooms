<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\ChangeBooking;
use App\Actions\Calendar\IssueCalendarFeed;
use App\Actions\Calendar\RevokeCalendarFeed;
use App\Bookings\Booking;
use App\Bookings\Upcoming;
use App\Livewire\Concerns\InvitesPeople;
use App\Livewire\Concerns\StaffComponent;
use App\Models\CalendarFeed;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use LynxCms\Core\Support\Value;

/**
 * "My bookings": the meetings the signed-in person organizes that have not ended (Upcoming), by day, over the next weeks
 * (each date of a series on its own), each with a form to move or rename it and a button to cancel
 * it (rooms.change, rooms.cancel); for a series, that date or the whole series. People invited
 * change for a whole meeting only. Below, the address of the person's own calendar feed.
 */
final class MyBookings extends Component
{
    use InvitesPeople;
    use StaffComponent;

    /** How far ahead the dates of series are listed. */
    public const int WEEKS_AHEAD = 6;

    /**
     * The booking being changed; opened is its time as it was opened, so a change to a whole series
     * leaves each date's own time unless the time itself was changed.
     *
     * @var array{booking: int, occurrence: string, repeats: string|null, series: bool, scope: string, date: string, from: string, to: string, opened: string, title: string, private: bool}|null
     */
    public ?array $editing = null;

    public ?string $notice = null;

    /** The feed address just issued; shown this once. */
    public ?string $feedUrl = null;

    public function edit(int $id, string $date): void
    {
        $booking = $this->own($id, $date);
        $this->editing = [
            'booking' => $booking->id, 'occurrence' => $booking->date, 'repeats' => $booking->repeats, 'series' => $booking->series !== null, 'scope' => 'one',
            'date' => $booking->date, 'from' => $booking->from, 'to' => $booking->to, 'opened' => $booking->from.'-'.$booking->to, 'title' => $booking->title, 'private' => $booking->isPrivate,
        ];
        $this->inviteIds($booking->attendees);
        $this->notice = null;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        if ($this->editing === null) {
            return;
        }

        $editing = $this->editing;
        $isSeries = $editing['scope'] === 'series';
        $input = ['booking' => $editing['booking'], 'title' => $editing['title'], 'private' => $editing['private']]
            + ($isSeries && $editing['opened'] === $editing['from'].'-'.$editing['to'] ? [] : ['from' => $editing['from'], 'to' => $editing['to']])
            + ($editing['repeats'] === null ? [] : ['occurrence' => $editing['occurrence'], 'scope' => $editing['scope']])
            + ($isSeries ? [] : ['date' => $editing['date']])
            + (self::mayInvite($editing) ? ['attendees' => $this->attendees] : []);

        if ($this->attemptAction(ChangeBooking::class, $input)) {
            $this->editing = null;
            $this->notice = __('staff.changed');
        }
    }

    public function cancel(int $id, string $date, string $scope = 'one'): void
    {
        $booking = $this->own($id, $date);
        $input = ['booking' => $id] + ($booking->repeats === null && $booking->series === null ? [] : ['occurrence' => $date, 'scope' => $scope === 'series' ? 'series' : 'one']);

        if ($this->attemptAction(CancelBooking::class, $input)) {
            $this->editing = null;
            $this->notice = __('staff.cancelled');
        }
    }

    public function issueFeed(): void
    {
        $this->feedUrl = Value::string(lynx()->run(IssueCalendarFeed::class)->value);
    }

    public function revokeFeed(): void
    {
        lynx()->run(RevokeCalendarFeed::class);
        $this->feedUrl = null;
    }

    /**
     * Who is invited changes for a whole meeting: a booking of its own, or a whole series.
     *
     * @param  array{series: bool, scope: string}  $editing
     */
    public static function mayInvite(array $editing): bool
    {
        return ! $editing['series'] || $editing['scope'] === 'series';
    }

    public function render(): View
    {
        $days = [];

        foreach (Upcoming::organizedBy((int) auth()->id(), self::WEEKS_AHEAD) as ['booking' => $booking, 'room' => $room]) {
            $days[$booking->date][] = ['booking' => $booking, 'room' => $room];
        }

        ksort($days);

        return view('livewire.my-bookings', [
            'days' => $days,
            'feed' => CalendarFeed::forUser((int) auth()->id()),
            'names' => User::query()->pluck('name', 'id')->all(),
            ...$this->people(auth()->id()),
        ]);
    }

    /** One of the signed-in person's own bookings, as on $date; others' are not theirs to touch here. */
    private function own(int $id, string $date): Booking
    {
        $booking = Booking::find($id, $date);
        abort_unless($booking !== null && (string) $booking->organizerId === (string) auth()->id(), 404);

        return $booking;
    }
}
