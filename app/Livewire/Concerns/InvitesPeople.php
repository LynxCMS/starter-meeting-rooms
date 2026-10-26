<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\User;

/**
 * The "Invite" picker of the booking forms: a dropdown of colleagues, each one picked joining the
 * list of people invited (by email, as rooms.book and rooms.change take them), and a button on
 * each to take them off again.
 */
trait InvitesPeople
{
    /** @var list<string> */
    public array $attendees = [];

    /** The colleague just picked in the dropdown; picking adds them and empties it again. */
    public string $invitee = '';

    public function updatedInvitee(string $email): void
    {
        if ($email !== '' && ! in_array($email, $this->attendees, true)) {
            $this->attendees[] = $email;
        }

        $this->invitee = '';
    }

    public function uninvite(string $email): void
    {
        $this->attendees = array_values(array_diff($this->attendees, [$email]));
    }

    /**
     * Who the picker offers and who it lists: everyone but the organizer, and those invited.
     *
     * @return array{invitable: list<User>, invited: list<User>}
     */
    private function people(int|string|null $organizer): array
    {
        $people = User::query()->orderBy('name')->get()->reject(fn (User $person): bool => (string) $person->id === (string) $organizer);

        return [
            'invitable' => array_values($people->reject(fn (User $person): bool => in_array($person->email, $this->attendees, true))->all()),
            'invited' => array_values($people->filter(fn (User $person): bool => in_array($person->email, $this->attendees, true))->all()),
        ];
    }

    /** @param  list<int>  $ids */
    private function inviteIds(array $ids): void
    {
        $this->attendees = array_values(User::query()->whereIn('id', $ids)->orderBy('name')->pluck('email')->map(fn (mixed $email): string => (string) $email)->all());
        $this->invitee = '';
    }
}
