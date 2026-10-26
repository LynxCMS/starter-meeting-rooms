<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Models\Room;
use Carbon\CarbonImmutable;
use LynxCms\Core\Support\Value;

/**
 * Where a room is, for invites, feeds and mail: its name, its office's city and address, and the
 * time zone its bookings' wall-clock times are in. Read once per room and process (requests, the
 * scheduler's commands); queued mail carries what it needs, so workers never read a stale one.
 */
final class Place
{
    /** @var array<int, self> */
    private static array $known = [];

    public function __construct(
        public readonly string $room,
        public readonly string $city,
        public readonly string $address,
        public readonly string $zone,
    ) {}

    public static function of(int $roomId): self
    {
        return self::$known[$roomId] ??= self::read($roomId);
    }

    /** "The Lodge, 1 Beaver Lodge Way, Vancouver, BC V6B 1A1": the room and its office's address. */
    public function location(): string
    {
        $address = trim(preg_replace('/\s*\n\s*/', ', ', $this->address) ?? '');

        return implode(', ', array_filter([$this->room, $address === '' ? $this->city : $address]));
    }

    /** A wall-clock time of this room as a moment. */
    public function at(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse("{$date} {$time}", $this->zone);
    }

    private static function read(int $roomId): self
    {
        $room = Room::query()->find($roomId);
        $page = $room?->page();
        $office = $page?->office;

        return new self(
            room: Value::string($page?->title, 'Room'),
            city: Value::string($office?->city),
            address: Value::string($office?->address),
            zone: $room?->timeZone() ?? Value::string(config('app.timezone'), 'UTC'),
        );
    }
}
