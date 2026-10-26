<?php

declare(strict_types=1);

namespace App\Bookings;

use Illuminate\Validation\ValidationException;

/**
 * A booking the office's rules refuse, with why ($reason) and the values the refusal names (a
 * room, times, minutes, a date), besides its English message. The admin, MCP clients and agents
 * read the message; the staff's booking pages show the refusal as a text editors write in each
 * language (resources/views/livewire/partials/refusal.blade.php).
 */
final class BookingRefused extends ValidationException
{
    public string $reason = '';

    /** @var array<string, string|int> */
    public array $values = [];

    /** @param  array<string, string|int>  $values */
    public static function because(string $field, string $reason, array $values, string $message): self
    {
        $refusal = self::withMessages([$field => $message]);
        $refusal->reason = $reason;
        $refusal->values = $values;

        return $refusal;
    }
}
