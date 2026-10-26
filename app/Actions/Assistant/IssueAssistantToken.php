<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Support\Value;

/**
 * Issues the person's token for their AI assistant and returns it: the only time it is shown. It
 * carries only the MCP server's ability (lynx.mcp.web.ability). A new one replaces the old.
 */
final class IssueAssistantToken extends Action
{
    use HandlesAssistantTokens;

    public static function name(): string
    {
        return 'rooms.issue_assistant_token';
    }

    public static function description(): string
    {
        return 'Issue your token for an AI assistant to book rooms as you over MCP (replacing the old one), and return it, shown only now.';
    }

    public function handle(Input $input, ActionContext $context): string
    {
        $user = $this->person($context);
        $replaced = $this->current($user)?->delete() === true;
        $token = $user->createToken(self::TOKEN_NAME, [Value::string(config('lynx.mcp.web.ability'), 'lynx:mcp')]);

        $context->changes->created('assistant_token', $token->accessToken->getKey(), ($replaced ? 'Replace' : 'Issue').' the assistant token of '.$user->name, ['user' => $user->id]);

        return $token->plainTextToken;
    }
}
