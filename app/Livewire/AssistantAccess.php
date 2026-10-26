<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Assistant\IssueAssistantToken;
use App\Actions\Assistant\RevokeAssistantToken;
use App\Livewire\Concerns\StaffComponent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;
use LynxCms\Core\Support\Value;

/**
 * On "My bookings": the person's token for an AI assistant (Claude and other MCP clients), which
 * then finds rooms and books, changes and cancels meetings as them, through the same actions and
 * rules as these pages. Shown once when issued, with the commands that connect a client.
 */
final class AssistantAccess extends Component
{
    use StaffComponent;

    /** The token just issued; shown this once. */
    public ?string $token = null;

    public function issue(): void
    {
        $this->token = Value::string(lynx()->run(IssueAssistantToken::class)->value);
    }

    public function revoke(): void
    {
        lynx()->run(RevokeAssistantToken::class);
        $this->token = null;
    }

    public function render(): View
    {
        $user = User::query()->find(auth()->id());
        $current = $user?->tokens()->where('name', IssueAssistantToken::TOKEN_NAME)->first();

        return view('livewire.assistant-access', [
            'isOn' => Value::bool(config('lynx.mcp.web.enabled')),
            'current' => $current instanceof PersonalAccessToken ? $current : null,
            'endpoint' => url(Value::string(config('lynx.mcp.web.path'), '/mcp/lynx')),
        ]);
    }
}
