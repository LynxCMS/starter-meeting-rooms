{{-- A meeting's mail (MeetingMail): what happened, then the meeting as it now stands. --}}
@php
    $locale = app()->getLocale();
    $day = fn (string $date): string => \Carbon\CarbonImmutable::parse($date)->locale($locale)->isoFormat('dddd D MMMM YYYY');
    $times = __('mail.times', ['from' => $summary->from, 'to' => $summary->to]);
    $isOneDate = in_array($kind, ['reminder', 'released'], true) && $summary->on !== null;
    $schedule = match (true) {
        $isOneDate => $day((string) $summary->on).', '.__('mail.times', ['from' => $summary->onFrom, 'to' => $summary->onTo]),
        $summary->repeat === null => $day($summary->date).', '.$times,
        default => __("mail.repeat_{$summary->repeat}", ['day' => \Carbon\CarbonImmutable::parse($summary->date)->locale($locale)->isoFormat('dddd')]).', '.$times.', '.__('mail.until', ['date' => $day((string) $summary->until)]),
    };
    $intro = match ($kind) {
        'booked' => $isOrganizer ? __('mail.intro_booked') : __('mail.intro_invitation', ['organizer' => $summary->organizer]),
        'reminder' => __('mail.intro_reminder', ['minutes' => $minutes]),
        'released' => __('mail.intro_released', ['minutes' => config('rooms.check_in_minutes')]),
        'cancelled' => $summary->on !== null && $summary->repeat !== null ? __('mail.on_cancelled', ['date' => $day($summary->on)]) : __('mail.intro_cancelled'),
        default => __("mail.intro_{$kind}"),
    };
@endphp
<x-mail::message>
# {{ $summary->title }}

{{ $intro }}

@if ($kind === 'changed' && $summary->on !== null && $summary->onFrom !== null)
**{{ __('mail.on_changed', ['date' => $day($summary->on), 'from' => $summary->onFrom, 'to' => $summary->onTo, 'room' => $summary->onRoom]) }}**

@endif
<x-mail::panel>
**{{ __('mail.when') }}** {{ $schedule }} ({{ __('mail.local_time', ['city' => $summary->city]) }})<br>
**{{ __('mail.where') }}** {{ $summary->location }}<br>
**{{ __('mail.organizer') }}** {{ $summary->organizer }}
@if ($summary->attendees !== [])
<br>**{{ __('mail.invited') }}** {{ implode(', ', $summary->attendees) }}
@endif
</x-mail::panel>

@if ($summary->isPrivate && $isOrganizer)
{{ __('mail.private_note') }}

@endif
@if ($ics !== null)
{{ $method === 'CANCEL' ? __('mail.ics_cancel') : __('mail.ics_request') }}

@endif
@if ($mine)
<x-mail::button :url="$mine">
{{ __('mail.my_bookings') }}
</x-mail::button>
@endif

{{ __('mail.signature') }}
</x-mail::message>
