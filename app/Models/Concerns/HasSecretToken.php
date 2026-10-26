<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * A secret link's token, kept only as its sha256 hash: the token itself is shown once, when the
 * link is issued. The model sets TOKEN_LENGTH and has a token_hash column.
 */
trait HasSecretToken
{
    /**
     * A new random token, and its hash for storing.
     *
     * @return array{0: string, 1: string}
     */
    public static function newToken(): array
    {
        $token = Str::random(static::TOKEN_LENGTH);

        return [$token, self::hash($token)];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?static
    {
        $link = strlen($token) === static::TOKEN_LENGTH ? static::query()->where('token_hash', self::hash($token))->first() : null;

        return $link instanceof static ? $link : null;
    }
}
