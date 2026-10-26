<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/**
 * Revokes the person's assistant token: the assistant holding it can no longer read or book.
 */
final class RevokeAssistantToken extends Action
{
    use HandlesAssistantTokens;

    public static function name(): string
    {
        return 'rooms.revoke_assistant_token';
    }

    public static function description(): string
    {
        return 'Revoke your AI assistant\'s token, so it can no longer read or book rooms as you.';
    }

    public static function destructive(): bool
    {
        return true;
    }

    public function handle(Input $input, ActionContext $context): bool
    {
        $user = $this->person($context);
        $token = $this->current($user);

        if ($token === null) {
            return false;
        }

        $token->delete();
        $context->changes->deleted('assistant_token', $token->getKey(), 'Revoke the assistant token of '.$user->name, ['user' => $user->id]);

        return true;
    }
}
