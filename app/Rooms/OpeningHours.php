<?php

declare(strict_types=1);

namespace App\Rooms;

use LynxCms\Core\Pages\Page;

/**
 * A room page's opening hours, grouped the way Zap repeats a schedule: the weekdays that share
 * the same hours, with those hours ("monday, tuesday 09:00 to 18:00", "friday 09:00 to 13:00").
 */
final class OpeningHours
{
    /**
     * @return list<array{days: list<string>, opens: string, closes: string}>
     */
    public static function of(Page $page): array
    {
        $groups = [];

        foreach ($page->opening_hours as $item) {
            $day = $item->weekday?->value;
            $opens = (string) $item->opens;
            $closes = (string) $item->closes;

            if ($day === null || $opens === '' || $closes === '' || $opens >= $closes) {
                continue;
            }

            $groups["{$opens}-{$closes}"]['days'][] = $day;
            $groups["{$opens}-{$closes}"] += ['opens' => $opens, 'closes' => $closes];
        }

        return array_values(array_map(
            fn (array $group): array => ['days' => array_values(array_unique($group['days'])), 'opens' => $group['opens'], 'closes' => $group['closes']],
            $groups,
        ));
    }
}
