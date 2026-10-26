<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Bookings\BookRoom;
use App\Bookings\Week;
use App\Livewire\Concerns\InvitesPeople;
use App\Livewire\Concerns\StaffComponent;
use App\Rooms\FreeRooms;
use App\Rooms\Slots;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * The staff's booking page: pick an office, a day, how long, how many seats and what equipment;
 * every room that fits shows its free start times; pick one, name the meeting, and it is booked
 * through rooms.book, with the office's rules and Zap's conflict check, and colleagues invited.
 */
final class BookARoom extends Component
{
    use InvitesPeople;
    use StaffComponent;

    public const array LENGTHS = [15, 30, 45, 60, 90, 120, 180, 240];

    #[Url]
    public ?int $office = null;

    #[Url]
    public string $date = '';

    #[Url]
    public int $minutes = 60;

    #[Url]
    public int $seats = 2;

    /** @var list<string> */
    #[Url]
    public array $equipment = [];

    /** @var array{room: int, from: string}|null the start time picked, waiting for a title */
    public ?array $picked = null;

    public string $title = '';

    public bool $private = false;

    /** '' (once), weekly, biweekly or monthly; with $until, the booking becomes a series. */
    public string $repeat = '';

    public string $until = '';

    /** @var array{room: string, date: string, from: string, to: string}|null */
    public ?array $booked = null;

    public function mount(): void
    {
        $first = Week::offices()->first();
        $this->office ??= $first === null ? null : (int) $first->id;
        $this->date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date) === 1 ? $this->date : $this->today();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['office', 'date', 'minutes', 'seats', 'equipment'], true)) {
            $this->picked = null;
        }
    }

    public function pick(int $room, string $from): void
    {
        $this->picked = ['room' => $room, 'from' => $from];
        $this->until = $this->until !== '' ? $this->until : CarbonImmutable::parse($this->date)->addMonths(3)->toDateString();
        $this->booked = null;
        $this->resetErrorBag();
    }

    public function book(): void
    {
        if ($this->picked === null) {
            return;
        }

        $to = Slots::plus($this->picked['from'], $this->minutes);
        $input = ['room' => $this->picked['room'], 'date' => $this->date, 'from' => $this->picked['from'], 'to' => $to, 'title' => $this->title, 'private' => $this->private]
            + ($this->repeat === '' ? [] : ['repeat' => $this->repeat, 'until' => $this->until])
            + ['attendees' => $this->attendees];

        if ($this->attemptAction(BookRoom::class, $input)) {
            $room = $this->roomPages()[$this->picked['room']] ?? null;
            $this->booked = ['room' => (string) $room?->title, 'date' => $this->date, 'from' => $this->picked['from'], 'to' => $to];
            $this->picked = null;
            $this->title = '';
            $this->private = false;
            $this->repeat = '';
            $this->attendees = [];
        }
    }

    public function render(): View
    {
        $minutes = in_array($this->minutes, self::LENGTHS, true) ? $this->minutes : 60;
        $rooms = FreeRooms::among($this->roomPages(), $this->date, $minutes, $this->seats, Value::strings($this->equipment));

        return view('livewire.book-a-room', [
            'offices' => Week::offices()->all(),
            'rooms' => $rooms,
            'today' => $this->today(),
            ...$this->people(auth()->id()),
        ]);
    }

    /** @return array<int, Page> the office's rooms, by page id */
    private function roomPages(): array
    {
        $pages = [];

        foreach ($this->office === null ? [] : Week::rooms($this->office) as $page) {
            $pages[(int) $page->id] = $page;
        }

        return $pages;
    }

    private function today(): string
    {
        $office = $this->office === null ? null : Week::offices()->filter(fn (Page $page): bool => (int) $page->id === $this->office)->first();

        return CarbonImmutable::now(Week::zone($office))->toDateString();
    }
}
