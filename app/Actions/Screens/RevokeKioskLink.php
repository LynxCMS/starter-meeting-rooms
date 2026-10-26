<?php

declare(strict_types=1);

namespace App\Actions\Screens;

use App\Models\KioskLink;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;

/** Revokes a screen's kiosk link: the screen stops showing bookings until a new link is issued. */
final class RevokeKioskLink extends Action
{
    use HandlesScreens;

    public static function name(): string
    {
        return 'screens.revoke_link';
    }

    public static function description(): string
    {
        return 'Revoke a screen\'s kiosk link, so the screen stops showing bookings.';
    }

    public static function permission(): string
    {
        return 'rooms.manage';
    }

    public static function destructive(): bool
    {
        return true;
    }

    public static function rules(): array
    {
        return ['screen' => ['required']];
    }

    public static function schema(JsonSchema $schema): array
    {
        return ['screen' => $schema->string()->description('Id or path of the screen page.')->required()];
    }

    public function handle(Input $input, ActionContext $context): bool
    {
        $screen = $this->screen($input->get('screen'));
        $link = KioskLink::forPage($screen) ?? throw ValidationException::withMessages(['screen' => "{$screen->title} has no kiosk link."]);
        $link->delete();
        $context->changes->deleted('kiosk_link', $link->id, 'Revoke the kiosk link of '.$screen->title);

        return true;
    }
}
