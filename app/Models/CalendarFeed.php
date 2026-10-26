<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSecretToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A calendar feed's secret address: a room's (page_id), at /calendar/rooms/{token}.ics, or a
 * person's (user_id), at /calendar/me/{token}.ics. One live address each: issuing a new one
 * (rooms.issue_feed) replaces the old.
 *
 * @property int $id
 * @property int|null $page_id
 * @property int|null $user_id
 * @property string $token_hash
 * @property int|null $created_by
 * @property Carbon|null $last_seen_at
 * @property Carbon $created_at
 */
final class CalendarFeed extends Model
{
    use HasSecretToken;

    public const int TOKEN_LENGTH = 40;

    protected $fillable = ['page_id', 'user_id', 'token_hash', 'created_by', 'last_seen_at'];

    public static function forRoom(int $pageId): ?self
    {
        return self::query()->where('page_id', $pageId)->first();
    }

    public static function forUser(int $userId): ?self
    {
        return self::query()->where('user_id', $userId)->first();
    }

    protected function casts(): array
    {
        return ['page_id' => 'integer', 'user_id' => 'integer', 'last_seen_at' => 'datetime'];
    }
}
