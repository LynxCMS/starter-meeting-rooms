<?php

declare(strict_types=1);

namespace App\Demo;

use App\Models\KioskLink;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * The public demo's screens, each with a link anyone may open. On a real site a kiosk link is a
 * secret; here its token comes from the app key, so the link stays the same across the hourly
 * reset and a screen left open comes back by itself.
 */
final class DemoScreens
{
    /** @return list<array{title: string, isLobby: bool, url: string}> */
    public static function all(): array
    {
        $pages = app(PageVisibility::class)->withoutRules(fn () => lynx()->pages->find('template=screen, include=all')->all());

        return array_map(fn (Page $screen): array => [
            'title' => (string) $screen->title,
            'isLobby' => $screen->kind?->value === 'lobby',
            'url' => self::link($screen),
        ], $pages);
    }

    private static function link(Page $screen): string
    {
        $token = substr(hash_hmac('sha256', "demo-screen-{$screen->id}", Value::string(config('app.key'))), 0, KioskLink::TOKEN_LENGTH);

        KioskLink::query()->updateOrCreate(['page_id' => (int) $screen->id], ['token_hash' => KioskLink::hash($token)]);

        return route('kiosk', ['token' => $token]);
    }
}
