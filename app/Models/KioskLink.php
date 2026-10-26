<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSecretToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;

/**
 * A screen's secret link (/screens/{token}). Only the token's hash is kept: the token itself is
 * shown once, when the link is issued (screens.issue_link), and works until it is revoked.
 *
 * @property int $id
 * @property int $page_id
 * @property string $token_hash
 * @property int|null $created_by
 * @property Carbon|null $last_seen_at
 * @property Carbon $created_at
 */
final class KioskLink extends Model
{
    use HasSecretToken;

    public const int TOKEN_LENGTH = 48;

    protected $fillable = ['page_id', 'token_hash', 'created_by', 'last_seen_at'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public static function forPage(Page $page): ?self
    {
        return self::query()->where('page_id', (int) $page->id)->first();
    }

    /** The screen page, whatever visitors may see: screens live in a hidden branch. */
    public function page(): ?Page
    {
        return app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($this->page_id));
    }
}
