<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Models\User;
use Carbon\CarbonImmutable;
use LynxCms\Core\Support\Value;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\Classification;
use Spatie\IcalendarGenerator\Enums\EventStatus;
use Spatie\IcalendarGenerator\Enums\ParticipationStatus;
use Spatie\IcalendarGenerator\Properties\DateTimeProperty;
use Spatie\IcalendarGenerator\Properties\TextProperty;

/**
 * iCalendar files: invites (iTIP REQUEST and CANCEL) that calendar apps file as one event and
 * update in place, and read-only feeds. Every invite carries the whole meeting as it now stands:
 * a series is one event with its RRULE, the dates taken out (EXDATE) and the dates changed on
 * their own (RECURRENCE-ID), so a change to one date re-sends the series rather than a fragment.
 */
final class Ics
{
    public const string REQUEST = 'REQUEST';

    public const string CANCEL = 'CANCEL';

    /** SEQUENCE counts seconds from here: each update outranks the last without a stored counter. */
    private const int SEQUENCE_EPOCH = 1767225600;

    private const int FEED_REFRESH_MINUTES = 15;

    /**
     * An invite, or a cancellation for $recipients (everyone, when null).
     *
     * @param  list<int>|null  $recipients
     */
    public static function invite(Meeting $meeting, string $method, ?array $recipients = null): string
    {
        $people = self::people($method === self::CANCEL && $recipients !== null ? $recipients : $meeting->people());
        $master = self::event($meeting, $meeting->first, $method === self::CANCEL ? EventStatus::Cancelled : EventStatus::Confirmed);
        self::address($master, $meeting, $people);

        if ($meeting->recurrence !== null) {
            $master->rruleAsString($meeting->recurrence->rrule($meeting->first->place()->zone));
            $master->doNotRepeatOn(array_map(fn (string $date): CarbonImmutable => $meeting->first->place()->at($date, $meeting->first->from), $meeting->excluded));
        }

        $events = [$master];

        foreach ($method === self::REQUEST ? $meeting->overrides() : [] as $override) {
            $event = self::event($meeting, $override, EventStatus::Confirmed);
            $event->appendProperty(DateTimeProperty::fromDateTime('RECURRENCE-ID', $meeting->first->place()->at($override->occurrence, $meeting->first->from), withTime: true));
            $events[] = self::address($event, $meeting, $people);
        }

        $calendar = self::calendar()->event($events);
        $calendar->appendProperty(TextProperty::create('METHOD', $method));

        return $calendar->get();
    }

    /**
     * A read-only feed: one event per date. With $isFull, titles and people show; without (a
     * room's feed, which anyone holding its link reads) private meetings are only "Busy".
     *
     * @param  list<array{meeting: Meeting, occurrence: Occurrence}>  $dates
     */
    public static function feed(string $name, array $dates, bool $isFull): string
    {
        $events = [];

        foreach ($dates as ['meeting' => $meeting, 'occurrence' => $occurrence]) {
            $booking = $meeting->booking;
            $isHidden = $booking->isPrivate && ! $isFull;
            $event = self::event($meeting, $occurrence, EventStatus::Confirmed)
                ->uniqueIdentifier(self::uid($meeting->key.'-'.str_replace('-', '', $occurrence->date)))
                ->name(match (true) {
                    $booking->isBlocked => Value::string(__('mail.feed_blocked', ['reason' => $occurrence->title], $booking->language)),
                    $isHidden => Value::string(__('mail.feed_busy', [], $booking->language)),
                    default => $occurrence->title,
                });

            if ($booking->isPrivate) {
                $event->classification(Classification::Private);
            }

            if (! $isHidden && ! $booking->isBlocked) {
                $event->description(self::description($meeting, self::people($meeting->people())));
            }

            $events[] = $event;
        }

        return self::calendar()->name($name)->refreshInterval(self::FEED_REFRESH_MINUTES)->event($events)->get();
    }

    public static function uid(string $key): string
    {
        return $key.'@'.(parse_url(Value::string(config('app.url')), PHP_URL_HOST) ?: 'localhost');
    }

    private static function calendar(): Calendar
    {
        return Calendar::create()->productIdentifier('-//'.Value::string(config('app.name'), 'Laravel').'//Meeting rooms//EN');
    }

    private static function event(Meeting $meeting, Occurrence $occurrence, EventStatus $status): Event
    {
        $place = $occurrence->place();

        return Event::create($occurrence->title)
            ->uniqueIdentifier(self::uid($meeting->key))
            ->sequence(max(1, CarbonImmutable::now()->getTimestamp() - self::SEQUENCE_EPOCH))
            ->startsAt($place->at($occurrence->date, $occurrence->from))
            ->endsAt($place->at($occurrence->date, $occurrence->to))
            ->address($place->location())
            ->status($status);
    }

    /**
     * The rooms' mailbox organizes every invite, so updates sent from it are accepted; the person
     * who booked comes first among the attendees, as accepted.
     *
     * @param  list<User>  $people
     */
    private static function address(Event $event, Meeting $meeting, array $people): Event
    {
        $event->organizer(Value::string(config('mail.from.address')), Value::string(config('mail.from.name')))
            ->description(self::description($meeting, $people));

        foreach ($people as $person) {
            $isOrganizer = (string) $person->id === (string) $meeting->booking->organizerId;
            $event->attendee($person->email, $person->name, $isOrganizer ? ParticipationStatus::Accepted : ParticipationStatus::NeedsAction);
        }

        return $event;
    }

    /** @param  list<User>  $people */
    private static function description(Meeting $meeting, array $people): string
    {
        $isOrganizer = fn (User $person): bool => (string) $person->id === (string) $meeting->booking->organizerId;
        $organizer = implode(', ', array_map(fn (User $person): string => $person->name, array_filter($people, $isOrganizer)));
        $others = array_map(fn (User $person): string => $person->name, array_filter($people, fn (User $person): bool => ! $isOrganizer($person)));

        return trim(Value::string(__('mail.ics_description', ['organizer' => $organizer, 'people' => implode(', ', $others) ?: '-'], $meeting->booking->language)));
    }

    /**
     * @param  list<int>  $ids
     * @return list<User>
     */
    private static function people(array $ids): array
    {
        return array_values(User::query()->whereIn('id', $ids)->orderBy('name')->get()->all());
    }
}
