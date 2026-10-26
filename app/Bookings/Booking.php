<?php

declare(strict_types=1);

namespace App\Bookings;

use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/**
 * A booking or a blocked time as the app reads it: a Zap appointment (or blocked schedule) of a
 * room, on one day, with its title, organizer, privacy, attendees (user ids) and language (the one
 * it was booked in, which its mail speaks) in Zap's metadata.
 */
final readonly class Booking
{
    public const string PRIVATE_TITLE = 'Busy';

    /** @param  list<int>  $attendees */
    public function __construct(
        public int $id,
        public int $roomId,
        public string $title,
        public string $date,
        public string $from,
        public string $to,
        public int|string|null $organizerId,
        public bool $isPrivate,
        public bool $isBlocked,
        public ?string $checkedInAt = null,
        public ?string $series = null,
        public ?string $repeats = null,
        public array $attendees = [],
        public string $language = 'en',
    ) {}

    /** A booking by id; for a series, the meeting on $date. */
    public static function find(int $id, ?string $date = null): ?self
    {
        $schedule = Schedule::query()->with('periods')->whereKey($id)
            ->whereIn('schedule_type', [ScheduleTypes::APPOINTMENT, ScheduleTypes::BLOCKED])->first();

        return $schedule instanceof Schedule ? self::fromSchedule($schedule, $date) : null;
    }

    /** A booking as read from its schedule; for a series, the meeting on $date. */
    public static function fromSchedule(Schedule $schedule, ?string $date = null): self
    {
        $period = $schedule->periods->first();
        $metadata = Value::map($schedule->metadata);

        return new self(
            id: (int) $schedule->id,
            roomId: (int) $schedule->schedulable_id,
            title: Value::string($schedule->name),
            date: $date ?? $schedule->start_date->toDateString(),
            from: substr(Value::string($period?->start_time), 0, 5),
            to: substr(Value::string($period?->end_time), 0, 5),
            organizerId: is_int($metadata['organizer_id'] ?? null) || is_string($metadata['organizer_id'] ?? null) ? $metadata['organizer_id'] : null,
            isPrivate: Value::bool($metadata['private'] ?? false),
            isBlocked: $schedule->schedule_type === ScheduleTypes::BLOCKED,
            checkedInAt: is_string($metadata['checked_in_at'] ?? null) ? $metadata['checked_in_at'] : null,
            series: Series::id($schedule),
            repeats: Series::pattern($schedule),
            attendees: self::ids($metadata['attendees'] ?? []),
            language: is_string($metadata['language'] ?? null) ? $metadata['language'] : 'en',
        );
    }

    /**
     * The organizer and the people invited, as user ids: who gets its mail.
     *
     * @return list<int>
     */
    public function people(): array
    {
        return array_values(array_unique([...(is_numeric($this->organizerId) ? [(int) $this->organizerId] : []), ...$this->attendees]));
    }

    /** What screens and other staff read: the title, or "Busy" for a private booking. */
    public function publicTitle(): string
    {
        return $this->isPrivate ? self::PRIVATE_TITLE : $this->title;
    }

    /** @return list<int> */
    private static function ids(mixed $ids): array
    {
        return array_values(array_map(intval(...), array_filter(is_array($ids) ? $ids : [], is_numeric(...))));
    }

    public function room(): ?Page
    {
        return Room::query()->find($this->roomId)?->page();
    }

    /** "Monday 2 November 2026 from 10:00 to 11:00", for summaries and messages. */
    public function when(): string
    {
        return CarbonImmutable::parse($this->date)->format('l j F Y')." from {$this->from} to {$this->to}";
    }
}
