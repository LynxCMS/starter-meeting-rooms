<?php

declare(strict_types=1);

namespace App\Events;

use App\Calendar\Summary;

/**
 * A meeting was booked, changed, cancelled or released: who hears of it, and what they are sent.
 * Booking actions record it with $context->event(), so it fires once the change is committed,
 * and never for a dry run. $invited get $request (the meeting as it now stands) when there is
 * one; $dropped, people no longer invited, get $cancel.
 */
final readonly class MeetingChanged
{
    public const string BOOKED = 'booked';

    public const string CHANGED = 'changed';

    public const string CANCELLED = 'cancelled';

    public const string RELEASED = 'released';

    /**
     * @param  list<int>  $invited
     * @param  list<int>  $dropped
     */
    public function __construct(
        public string $kind,
        public Summary $summary,
        public string $language,
        public int|string|null $organizerId,
        public array $invited,
        public ?string $request = null,
        public array $dropped = [],
        public ?string $cancel = null,
    ) {}
}
