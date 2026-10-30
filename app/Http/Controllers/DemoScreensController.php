<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Demo\DemoScreens;
use Illuminate\Contracts\View\View;

/** The public demo's list of screens (/demo/screens). Answers 404 anywhere else. */
final class DemoScreensController
{
    public function __invoke(): View
    {
        abort_unless(config('rooms.demo_reset') === true, 404);

        return view('demo.screens', ['screens' => DemoScreens::all()]);
    }
}
