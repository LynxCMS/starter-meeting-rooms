<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use LynxCms\Core\Actions\Runner\ActionContext;

/**
 * What the assistant token actions share. A person has one token for their AI assistant, which
 * opens the MCP server (and nothing else) as them. Issued and revoked from their own page only:
 * an assistant cannot mint itself keys, and never sees one.
 */
trait HandlesAssistantTokens
{
    /** The token's name, among the person's Sanctum tokens. */
    public const string TOKEN_NAME = 'assistant';

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public static function exposedAsTool(): bool
    {
        return false;
    }

    public static function rules(): array
    {
        return [];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [];
    }

    /** The person running the action. */
    private function person(ActionContext $context): User
    {
        $user = is_numeric($context->actor->id) ? User::query()->find((int) $context->actor->id) : null;

        return $user instanceof User ? $user : throw ValidationException::withMessages(['token' => 'Only people have an assistant token.']);
    }

    private function current(User $user): ?PersonalAccessToken
    {
        $token = $user->tokens()->where('name', self::TOKEN_NAME)->first();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
