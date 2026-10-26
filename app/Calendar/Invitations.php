<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Events\MeetingChanged;
use LynxCms\Core\Actions\Runner\ActionContext;

/**
 * Who hears of a booking change, from the booking actions: the meeting before and after it. Those
 * still invited get the meeting as it now stands; those no longer invited (or everyone, when the
 * meeting is gone) get a cancellation. Blocked times have nobody to tell.
 */
final class Invitations
{
    /** With $date, the change concerns that one date of a series. */
    public static function send(ActionContext $context, string $kind, ?Meeting $before, ?Meeting $after, ?string $date = null): void
    {
        $meeting = $after ?? $before;

        if ($meeting === null || $meeting->booking->isBlocked) {
            return;
        }

        $invited = $after?->people() ?? [];
        $dropped = array_values(array_diff($before?->people() ?? [], $invited));

        if ($invited === [] && $dropped === []) {
            return;
        }

        $context->event(new MeetingChanged(
            kind: $after === null ? MeetingChanged::CANCELLED : $kind,
            summary: Summary::of($meeting, $date),
            language: $meeting->booking->language,
            organizerId: $meeting->booking->organizerId,
            invited: $invited,
            request: $after === null ? null : Ics::invite($after, Ics::REQUEST),
            dropped: $dropped,
            cancel: $before === null || $dropped === [] ? null : Ics::invite($before, Ics::CANCEL, $dropped),
        ));
    }

    /** A no-show: the organizer hears the room was released. Calendars keep the meeting. */
    public static function released(ActionContext $context, Meeting $meeting, string $date): void
    {
        $organizer = $meeting->booking->organizerId;

        if (! is_numeric($organizer)) {
            return;
        }

        $context->event(new MeetingChanged(
            kind: MeetingChanged::RELEASED,
            summary: Summary::of($meeting, $date),
            language: $meeting->booking->language,
            organizerId: $organizer,
            invited: [(int) $organizer],
        ));
    }
}
