<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Bookings\Week;
use App\Livewire\Admin\Concerns\EditsBookings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use LynxCms\Core\Pages\Page;

/**
 * The admin's Bookings screen: one office's rooms against the days of a working week, every
 * booking and blocked time in its cell, and the form to book, block, change or cancel.
 */
final class RoomsWeek extends Component
{
    use EditsBookings;

    #[Url]
    public ?int $office = null;

    #[Url]
    public ?string $week = null;

    public function mount(): void
    {
        $first = Week::offices()->first();
        $this->office ??= $first === null ? null : (int) $first->id;
        $this->week = Week::monday($this->week, Week::zone($this->officePage()));
    }

    public function updatedOffice(): void
    {
        $this->closeForm();
    }

    public function previousWeek(): void
    {
        $this->shiftWeek(-7);
    }

    public function nextWeek(): void
    {
        $this->shiftWeek(7);
    }

    public function thisWeek(): void
    {
        $this->week = Week::monday(null, Week::zone($this->officePage()));
    }

    public function render(): View
    {
        $office = $this->officePage();
        $rooms = $this->office === null ? [] : Week::rooms($this->office)->all();
        $days = Week::days((string) $this->week);
        $grid = Week::bookings($rooms, $days);

        return view('livewire.admin.rooms-week', [
            'offices' => Week::offices()->all(),
            'officePage' => $office,
            'zone' => Week::zone($office),
            'rooms' => $rooms,
            'days' => $days,
            'grid' => $grid,
            'organizers' => Week::organizers($grid),
            ...$this->formPeople(),
            'today' => CarbonImmutable::now(Week::zone($office))->toDateString(),
        ]);
    }

    private function officePage(): ?Page
    {
        return $this->office === null ? null : Week::offices()->filter(fn (Page $page): bool => (int) $page->id === $this->office)->first();
    }

    private function shiftWeek(int $days): void
    {
        $this->week = CarbonImmutable::parse((string) $this->week)->addDays($days)->toDateString();
        $this->closeForm();
    }
}
