<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Seeders\SiteSchemaSeeder;
use Illuminate\Database\Eloquent\Model;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;
use Zap\Models\Concerns\HasSchedules;

/**
 * A meeting room as Zap sees it: the schedulable its availability, bookings and blocked times
 * belong to. Its name, photo, capacity, opening hours and office are on its Lynx page (template
 * "room"), which RoomSync keeps this row in step with.
 *
 * @property int $id
 * @property int $page_id
 */
final class Room extends Model
{
    use HasSchedules;

    protected $fillable = ['page_id'];

    public static function forPage(Page $page): ?self
    {
        return self::query()->where('page_id', $page->id)->first();
    }

    /** The room a page path ("/rooms/lodge/") or page id names, whatever visitors may see. */
    public static function byReference(mixed $reference): ?self
    {
        $page = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get(is_numeric($reference) ? (int) $reference : Value::string($reference)));

        return $page instanceof Page && (string) $page->template()->name === 'room' ? self::forPage($page) : null;
    }

    /**
     * The room's page, or null when it is gone. Read past visibility rules: rooms sit behind the
     * Rooms section's password, which is for visitors, not for the app's own work.
     */
    public function page(): ?Page
    {
        return app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($this->page_id));
    }

    /**
     * The room's office's time zone: bookings are wall-clock times in it, and "now" and "today"
     * for the room are read in it. The app's zone when the room has no office.
     */
    public function timeZone(): string
    {
        $value = $this->page()?->office?->time_zone?->value;
        $zone = SiteSchemaSeeder::TIME_ZONES[(string) $value]['zone'] ?? null;

        return $zone ?? Value::string(config('app.timezone'), 'UTC');
    }

    /** Today in the room's time zone, as YYYY-MM-DD. */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timeZone())->startOfDay();
    }

    protected function casts(): array
    {
        return ['page_id' => 'integer'];
    }
}
