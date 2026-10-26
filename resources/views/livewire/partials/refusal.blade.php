{{--
    Why the office's rules refused a booking, as Page Texts of the booking page ($page), which
    editors reword in each language. $refusal is App\Bookings\BookingRefused's reason and values;
    a refusal without a text here shows the action's own message ($message).
--}}
@php
    $values = $refusal['values'];
    $day = isset($values['date']) ? \Carbon\CarbonImmutable::parse((string) $values['date'])->locale(app()->getLocale())->dayName : '';
@endphp
<p class="text-sm font-medium text-copper {{ $span ?? '' }}" role="alert">
    @switch($refusal['reason'])
        @case('backwards')
            @string('refusal.backwards', ['en' => 'A meeting must end after it starts.', 'fr' => 'Une réunion doit finir après son début.'], label: 'Refused: ends before it starts')
            @break
        @case('too_short')
            @string('refusal.too_short', ['en' => 'A meeting lasts at least :minutes minutes.', 'fr' => 'Une réunion dure au moins :minutes minutes.'], label: 'Refused: too short', replace: ['minutes' => $values['minutes'] ?? ''])
            @break
        @case('too_long')
            @string('refusal.too_long', ['en' => 'A meeting lasts at most :minutes minutes.', 'fr' => 'Une réunion dure au plus :minutes minutes.'], label: 'Refused: too long', replace: ['minutes' => $values['minutes'] ?? ''])
            @break
        @case('past')
            @string('refusal.past', ['en' => 'That time has already passed.', 'fr' => 'Ce moment est déjà passé.'], label: 'Refused: in the past')
            @break
        @case('closed')
            @string('refusal.closed', ['en' => ':room is closed on :day.', 'fr' => 'La salle :room est fermée le :day.'], label: 'Refused: room closed that day', replace: ['room' => $values['room'] ?? '', 'day' => $day])
            @break
        @case('closed_then')
            @string('refusal.closed_then', ['en' => ':room is open :hours on :day.', 'fr' => 'La salle :room est ouverte :hours le :day.'], label: 'Refused: outside opening hours', replace: ['room' => $values['room'] ?? '', 'hours' => $values['hours'] ?? '', 'day' => $day])
            @break
        @case('taken')
            @string('refusal.taken', ['en' => ':room is taken during :times.', 'fr' => 'La salle :room est prise pendant :times.'], label: 'Refused: room taken (times)', replace: ['room' => $values['room'] ?? '', 'times' => $values['times'] ?? ''])
            @if ((int) ($values['buffer'] ?? 0) > 0)
                @string('refusal.gap', ['en' => 'Meetings need :minutes minutes between them.', 'fr' => 'Il faut :minutes minutes entre deux réunions.'], label: 'Refused: the gap between meetings', replace: ['minutes' => $values['buffer']])
            @endif
            @break
        @case('unknown_person')
            @string('refusal.unknown_person', ['en' => 'There is no one at :email to invite.', 'fr' => 'Personne à inviter à l’adresse :email.'], label: 'Refused: unknown colleague', replace: ['email' => $values['email'] ?? ''])
            @break
        @case('no_meeting')
            @string('refusal.no_meeting', ['en' => 'This series has no meeting that day.', 'fr' => 'Cette série n’a pas de réunion ce jour-là.'], label: 'Refused: no meeting of the series that day')
            @break
        @default
            {{ $message }}
    @endswitch
</p>
