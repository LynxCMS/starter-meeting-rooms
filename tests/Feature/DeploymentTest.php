<?php

declare(strict_types=1);

/*
 * What the Docker image relies on besides the code: settings that keep the site fast on SQLite.
 */

it('keeps sessions in files, since SQLite takes one write at a time', function (): void {
    expect((string) file_get_contents(base_path('Dockerfile')))->toMatch('/^\s+SESSION_DRIVER=file\b/m')
        ->and((string) file_get_contents(base_path('.env.example')))->toMatch('/^SESSION_DRIVER=file$/m');
});
