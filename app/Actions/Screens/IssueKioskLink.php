<?php

declare(strict_types=1);

namespace App\Actions\Screens;

use App\Models\KioskLink;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/**
 * Issues a screen's secret link and returns its address, the only time the token is ever shown.
 * A screen has one live link: issuing a new one revokes the old, so a lost tablet is cut off.
 */
final class IssueKioskLink extends Action
{
    use HandlesScreens;

    public static function name(): string
    {
        return 'screens.issue_link';
    }

    public static function description(): string
    {
        return 'Issue a screen\'s secret kiosk link (replacing its old one) and return the address, shown only now.';
    }

    public static function permission(): string
    {
        return 'rooms.manage';
    }

    public static function rules(): array
    {
        return ['screen' => ['required']];
    }

    public static function schema(JsonSchema $schema): array
    {
        return ['screen' => $schema->string()->description('Id or path of the screen page.')->required()];
    }

    public function handle(Input $input, ActionContext $context): string
    {
        $screen = $this->screen($input->get('screen'));
        [$token, $hash] = KioskLink::newToken();
        $replaced = KioskLink::forPage($screen)?->delete() === true;

        $link = KioskLink::query()->create([
            'page_id' => (int) $screen->id,
            'token_hash' => $hash,
            'created_by' => is_numeric($context->actor->id) ? (int) $context->actor->id : null,
        ]);

        $context->changes->created('kiosk_link', $link->id, ($replaced ? 'Replace' : 'Issue').' the kiosk link of '.$screen->title, ['screen' => (int) $screen->id]);

        return route('kiosk', ['token' => $token]);
    }
}
