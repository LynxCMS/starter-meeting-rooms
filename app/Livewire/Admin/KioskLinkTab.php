<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Screens\IssueKioskLink;
use App\Actions\Screens\RevokeKioskLink;
use App\Models\KioskLink;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\AdminServiceProvider;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * The Kiosk link tab of a screen's page editor: whether the screen has a link, issuing a new one
 * (its address and QR code are shown this once) and revoking it.
 */
final class KioskLinkTab extends Component
{
    private const int QR_SIZE = 220;

    public int $pageId;

    /** The address just issued; it lives only in this component's state until the tab is left. */
    public ?string $issued = null;

    public function boot(): void
    {
        Gate::authorize(AdminServiceProvider::GATE);
        abort_unless(app(AdminAccess::class)->allows('rooms.manage'), 403);
    }

    public function issue(): void
    {
        $this->issued = Value::string(lynx()->run(IssueKioskLink::class, ['screen' => $this->pageId])->value);
    }

    public function revoke(): void
    {
        lynx()->run(RevokeKioskLink::class, ['screen' => $this->pageId]);
        $this->issued = null;
    }

    public function render(): View
    {
        $screen = $this->screen();

        return view('livewire.admin.kiosk-link', [
            'screen' => $screen,
            'link' => KioskLink::forPage($screen),
            'qr' => $this->issued === null ? null : $this->qr($this->issued),
        ]);
    }

    private function screen(): Page
    {
        $page = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get($this->pageId));
        abort_unless($page instanceof Page && (string) $page->template()->name === 'screen', 404);

        return $page;
    }

    /** The address as an SVG QR code, to scan from the screen's own browser. */
    private function qr(string $url): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(self::QR_SIZE, 1), new SvgImageBackEnd)))->writeString($url);
    }
}
