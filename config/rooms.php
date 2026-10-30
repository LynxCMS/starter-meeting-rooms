<?php

declare(strict_types=1);

return [
    // For the public demo only, where anyone may sign in: every hour the site goes back to the seeded
    // firm and drops uploads (rooms:reset-demo), and the admin's sidebar says so.
    'demo_reset' => (bool) env('SITE_DEMO_RESET', false),

    // The accounts the demo's sign-in page offers, one click each, and where each one lands.
    'demo_accounts' => [
        'admin' => ['label' => 'Admin', 'email' => 'owner@meeting-rooms.example', 'home' => '/lynx/pages'],
        'editor' => ['label' => 'Editor', 'email' => 'mia@meeting-rooms.example', 'home' => '/lynx/bookings'],
        'staff' => ['label' => 'Staff', 'email' => 'ana@meeting-rooms.example', 'home' => '/book'],
    ],

    // Password of the demo owner the seeder creates (owner@meeting-rooms.example).
    'owner_password' => env('ROOMS_OWNER_PASSWORD', 'change-me-now'),

    // The password of the Rooms section, which covers every room. Choose a passphrase.
    'section_password' => env('ROOMS_PASSWORD', 'four otters and a beaver'),

    // Shortest and longest meeting, in minutes. Blocked times may be longer.
    'min_minutes' => (int) env('ROOMS_MIN_MINUTES', 15),
    'max_minutes' => (int) env('ROOMS_MAX_MINUTES', 240),

    // Minutes kept free after each meeting, before the next may start (Zap's buffer).
    'buffer_minutes' => (int) env('ROOMS_BUFFER_MINUTES', 5),

    // In rooms that need a check in, a meeting nobody checked into within this many minutes of
    // its start is released: the rest of it is freed for others.
    'check_in_minutes' => (int) env('ROOMS_CHECK_IN_MINUTES', 10),

    // A door screen offers Check in from this many minutes before a meeting starts.
    'check_in_early_minutes' => (int) env('ROOMS_CHECK_IN_EARLY_MINUTES', 5),

    // Minutes before a meeting its organizer and the people invited get a reminder; 0 turns them off.
    'reminder_minutes' => (int) env('ROOMS_REMINDER_MINUTES', 15),

    // How often screens ask for news, in seconds; their clock and progress move every second.
    'screen_poll_seconds' => (int) env('ROOMS_SCREEN_POLL_SECONDS', 20),

    // The hour (in the screen's own clock) screens reload themselves, to pick up new code.
    'screen_reload_hour' => (int) env('ROOMS_SCREEN_RELOAD_HOUR', 3),

    // Nightly copies of the SQLite database kept by rooms:backup-database.
    'database_backups_keep' => (int) env('ROOMS_DATABASE_BACKUPS_KEEP', 14),
];
