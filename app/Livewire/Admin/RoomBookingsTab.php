<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Bookings\Week;
use App\Livewire\Admin\Concerns\EditsBookings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;

/**
 * The Bookings tab of a room's page editor: the room's working week, day by day, with the form to
 * book, block, change or cancel.
 */
final class RoomBookingsTab extends Component
{
    use EditsBookings;

    public int $pageId;

    public ?string $week = null;

    public function mount(): void
    {
        $this->week = Week::monday(null, $this->zone());
    }

    public function previousWeek(): void
    {
        $this->week = CarbonImmutable::parse((string) $this->week)->subWeek()->toDateString();
        $this->closeForm();
    }

    public function nextWeek(): void
    {
        $this->week = CarbonImmutable::parse((string) $this->week)->addWeek()->toDateString();
        $this->closeForm();
    }

    public function thisWeek(): void
    {
        $this->week = Week::monday(null, $this->zone());
    }

    public function render(): View
    {
        $room = $this->room();
        $days = Week::days((string) $this->week);
        $grid = Week::bookings([$room], $days);

        return view('livewire.admin.room-bookings', [
            'room' => $room,
            'days' => $days,
            'grid' => $grid,
            'organizers' => Week::organizers($grid),
            ...$this->formPeople(),
            'zone' => $this->zone(),
            'today' => CarbonImmutable::now($this->zone())->toDateString(),
        ]);
    }

    private function room(): Page
    {
        $page = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($this->pageId));
        abort_unless($page instanceof Page && (string) $page->template()->name === 'room', 404);

        return $page;
    }

    private function zone(): string
    {
        return Week::zone($this->room()->office);
    }
}
