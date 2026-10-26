<?php

declare(strict_types=1);

namespace App\Mail;

use App\Calendar\Ics;
use App\Calendar\Summary;
use App\Events\MeetingChanged;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use LynxCms\Core\Support\Value;

/**
 * A meeting's mail to one person: booked, an invitation, changed, cancelled, released for a
 * no-show, or a reminder. Invites and cancellations carry the .ics, as text/calendar with its
 * iTIP method, so calendar apps offer to add or update the event in place.
 */
final class MeetingMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public const string REMINDER = 'reminder';

    public const string UNINVITED = 'uninvited';

    public function __construct(
        public readonly string $kind,
        public readonly Summary $summary,
        public readonly bool $isOrganizer,
        public readonly ?string $ics = null,
        public readonly string $method = Ics::REQUEST,
        public readonly int $minutes = 0,
    ) {
        $this->theme = 'castor';
    }

    public function envelope(): Envelope
    {
        $title = $this->kind === self::REMINDER && $this->summary->isPrivate ? __('mail.private_title') : $this->summary->title;
        $subject = $this->kind === MeetingChanged::BOOKED && ! $this->isOrganizer ? 'invitation' : $this->kind;

        return new Envelope(subject: Value::string(__("mail.subject_{$subject}", [
            'title' => $title,
            'room' => $this->summary->onRoom ?? $this->summary->room,
            'minutes' => $this->minutes,
            'when' => $this->moment(),
        ])));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.meeting', with: ['summary' => $this->summary, 'when' => $this->moment()]);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        if ($this->ics === null) {
            return [];
        }

        return [
            Attachment::fromData(fn (): string => (string) $this->ics, $this->method === Ics::CANCEL ? 'cancel.ics' : 'invite.ics')
                ->withMime('text/calendar; charset=UTF-8; method='.$this->method),
        ];
    }

    /** "Mon 2 Nov, 10:00" for the date concerned, or the meeting's (its first, for a series). */
    private function moment(): string
    {
        $date = $this->summary->on ?? $this->summary->date;
        $time = $this->summary->on === null ? $this->summary->from : ($this->summary->onFrom ?? $this->summary->from);

        // Mail renders in its locale, which Laravel hands to Carbon too.
        return CarbonImmutable::parse($date)->isoFormat('ddd D MMM').', '.$time;
    }
}
