<?php

// Meeting mail (MeetingMail, resources/views/mail/meeting.blade.php) and calendar text.
return [
    'subject_booked' => 'Booked: :title, :when',
    'subject_invitation' => 'Invitation: :title, :when',
    'subject_changed' => 'Updated: :title, :when',
    'subject_cancelled' => 'Cancelled: :title, :when',
    'subject_uninvited' => 'Cancelled: :title, :when',
    'subject_released' => 'Released: :title in :room',
    'subject_reminder' => 'In :minutes minutes: :title in :room',
    'private_title' => 'Your private meeting',

    'intro_booked' => 'Your meeting is booked. The invite is attached for your calendar.',
    'intro_invitation' => ':organizer invited you to a meeting.',
    'intro_changed' => 'This meeting has changed. Here it is as it now stands.',
    'intro_cancelled' => 'This meeting is cancelled.',
    'intro_uninvited' => 'You are no longer invited to this meeting.',
    'intro_released' => 'Nobody checked in within :minutes minutes of the start, so the rest of the room\'s time was released for others.',
    'intro_reminder' => 'Your meeting starts in :minutes minutes.',
    'on_changed' => 'This change is for :date: now :from to :to, in :room.',
    'on_cancelled' => 'Only :date is cancelled. The other dates stand.',

    'when' => 'When:',
    'where' => 'Where:',
    'organizer' => 'Organizer:',
    'invited' => 'Invited:',
    'times' => ':from to :to',
    'until' => 'until :date',
    'local_time' => ':city time',
    'repeat_weekly' => 'Every :day',
    'repeat_biweekly' => 'Every other :day',
    'repeat_monthly' => 'Monthly, on the same :day of the month',

    'private_note' => 'It is private: screens and colleagues see only "Busy".',
    'ics_request' => 'Open the attached invite to add it to your calendar, or to update the copy already there.',
    'ics_cancel' => 'Open the attached file to take it out of your calendar.',
    'my_bookings' => 'My bookings',
    'signature' => 'The rooms at Castor & Otters',

    'footer' => 'Sent by the meeting rooms, who never double-book.',

    'ics_description' => "Organizer: :organizer\nInvited: :people",
    'feed_mine' => ':name: meetings',
    'feed_busy' => 'Busy',
    'feed_blocked' => 'Blocked: :reason',
];
