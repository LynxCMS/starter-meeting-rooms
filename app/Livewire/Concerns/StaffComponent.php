<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Bookings\BookingRefused;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Languages\LanguageContext;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Permissions\Authorizer;

/**
 * What the staff's booking components share. They sit on Lynx pages (templates "book" and
 * "my-bookings"), whose Page Texts their views use, so they keep the page's id. Livewire answers
 * at its own address, without the page's /fr prefix, so the component keeps the visitor's
 * language and restores it on every request; and it checks, on every request too, that a
 * signed-in person may book rooms. A refusal by the office's rules is kept with its reason and
 * values, which the view shows as the booking page's own text.
 */
trait StaffComponent
{
    public string $lang = '';

    /** The Lynx page the component sits on. */
    #[Locked]
    public int $pageId = 0;

    /** @var array{reason: string, values: array<string, string|int>}|null the last refusal, if the rules gave one */
    public ?array $refusal = null;

    public function bootStaffComponent(): void
    {
        $id = auth()->id();
        abort_unless($id !== null && app(Authorizer::class)->allows(Actor::user($id), 'rooms.book'), 403);
    }

    public function mountStaffComponent(): void
    {
        $this->lang = (string) app(LanguageContext::class)->current()->code;
    }

    public function hydrateStaffComponent(): void
    {
        if (lynx()->languages->find($this->lang) !== null) {
            app(LanguageContext::class)->set($this->lang);
        }
    }

    /**
     * Runs a Lynx action, showing its refusal on the form instead of failing the request.
     *
     * @param  class-string  $action
     * @param  array<string, mixed>  $input
     */
    private function attemptAction(string $action, array $input): bool
    {
        $this->resetErrorBag();
        $this->refusal = null;

        try {
            lynx()->run($action, $input);
        } catch (ValidationException $exception) {
            if ($exception instanceof BookingRefused) {
                $this->refusal = ['reason' => $exception->reason, 'values' => $exception->values];
            }

            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Not valid.');
            }

            return false;
        }

        return true;
    }

    /**
     * What the view needs besides the component's own data: the page it sits on, for its Page
     * Texts; the booking page, whose texts say why a booking was refused on either page; and the
     * My bookings page, linked from a booking's confirmation.
     *
     * @return array{page: Page|null, bookingPage: Page|null, myBookingsPage: Page|null}
     */
    private function pages(): array
    {
        return [
            'page' => $this->pageId > 0 ? lynx()->pages->get($this->pageId) : null,
            'bookingPage' => lynx()->pages->find('template=book, include=hidden')->first(),
            'myBookingsPage' => lynx()->pages->find('template=my-bookings, include=hidden')->first(),
        ];
    }
}
