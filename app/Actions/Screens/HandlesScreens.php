<?php

declare(strict_types=1);

namespace App\Actions\Screens;

use Illuminate\Validation\ValidationException;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;

/** What the kiosk link actions share: finding the screen page a path or id names. */
trait HandlesScreens
{
    private function screen(mixed $reference): Page
    {
        $page = app(PageVisibility::class)->withoutRules(
            fn (): ?Page => lynx()->pages->get(is_numeric($reference) ? (int) $reference : (string) $reference),
        );

        return $page instanceof Page && (string) $page->template()->name === 'screen'
            ? $page
            : throw ValidationException::withMessages(['screen' => 'There is no screen ['.(is_scalar($reference) ? $reference : '').'].']);
    }
}
