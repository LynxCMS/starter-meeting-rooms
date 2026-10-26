<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Calendar\Ics;
use App\Events\MeetingChanged;
use App\Mail\MeetingMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** Queues a meeting's mail to each person concerned, in the language the meeting was booked in. */
final class SendMeetingMails
{
    public function handle(MeetingChanged $event): void
    {
        foreach (User::query()->whereIn('id', $event->invited)->get() as $person) {
            Mail::to($person)->locale($event->language)
                ->queue(new MeetingMail($event->kind, $event->summary, $this->isOrganizer($person, $event), $event->request, Ics::REQUEST));
        }

        foreach (User::query()->whereIn('id', $event->dropped)->get() as $person) {
            $kind = $event->kind === MeetingChanged::CANCELLED ? MeetingChanged::CANCELLED : MeetingMail::UNINVITED;
            Mail::to($person)->locale($event->language)
                ->queue(new MeetingMail($kind, $event->summary, $this->isOrganizer($person, $event), $event->cancel, Ics::CANCEL));
        }
    }

    private function isOrganizer(User $person, MeetingChanged $event): bool
    {
        return (string) $person->id === (string) $event->organizerId;
    }
}
