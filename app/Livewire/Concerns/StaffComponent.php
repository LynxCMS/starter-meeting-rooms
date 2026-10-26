<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Languages\LanguageContext;
use LynxCms\Core\Permissions\Authorizer;

/**
 * What the staff's booking components share. Livewire answers at its own address, without the
 * page's /fr prefix, so the component keeps the visitor's language and restores it on every
 * request; and it checks, on every request too, that a signed-in person may book rooms.
 */
trait StaffComponent
{
    public string $lang = '';

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

        try {
            lynx()->run($action, $input);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Not valid.');
            }

            return false;
        }

        return true;
    }
}
