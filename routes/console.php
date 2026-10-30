<?php

use App\Actions\Bookings\ReleaseNoShows;
use App\Calendar\Reminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Frees the rest of meetings nobody checked into, in rooms that need a check in (rooms.check_in_minutes).
Artisan::command('rooms:release-no-shows', function () {
    $released = lynx()->run(ReleaseNoShows::class)->value;
    $this->info(count(is_array($released) ? $released : []).' meeting(s) released.');
})->purpose('Release meetings nobody checked into');

Schedule::command('rooms:release-no-shows')->everyMinute()->withoutOverlapping();

// Mails a reminder rooms.reminder_minutes before each meeting, to its organizer and the people invited.
Artisan::command('rooms:send-reminders', function (Reminders $reminders) {
    $this->info($reminders->send().' meeting(s) reminded.');
})->purpose('Send reminders before meetings');

Schedule::command('rooms:send-reminders')->everyMinute()->withoutOverlapping();

// A consistent copy of the SQLite database, in storage/app/private/backups: bookings, accounts,
// kiosk links and tokens live in it, and Lynx's content backups (lynx:backup) leave them out.
Artisan::command('rooms:backup-database', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->warn('Only SQLite databases are copied here; back up others with their own tools.');

        return;
    }

    $folder = storage_path('app/private/backups');
    File::ensureDirectoryExists($folder);
    $file = $folder.'/database-'.now()->format('Y-m-d-His').'.sqlite';
    DB::statement('VACUUM INTO ?', [$file]);

    $copies = File::glob($folder.'/database-*.sqlite');
    rsort($copies);
    File::delete(array_slice($copies, max(1, (int) config('rooms.database_backups_keep'))));

    $this->info("Copied to {$file}.");
})->purpose('Copy the SQLite database to storage/app/private/backups');

Schedule::command('rooms:backup-database')->dailyAt('02:30')->withoutOverlapping();

// The public demo goes back to its seeded firm every hour (config rooms.demo_reset).
Schedule::command('rooms:reset-demo')->hourly()->when(fn (): bool => config('rooms.demo_reset') === true);
