<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Core\Actions\Roles\SaveRole;
use LynxCms\Core\Support\Value;
use LynxCms\PagePassword\Actions\EnablePagePassword;
use LynxCms\PagePassword\PagePasswordSchema;

/**
 * The meeting rooms' fields and templates: rooms with their opening hours, the screens that show
 * them, lobby announcements and the site's settings. Run once on a fresh install, then keep
 * environments in step with the schema files (php artisan lynx:schema:export).
 */
final class RoomsSchemaSeeder extends Seeder
{
    private const array WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function run(): void
    {
        $this->fields();
        $this->templates();
        $this->roles();
    }

    /** Staff book rooms; managers also open the admin, edit rooms and manage everyone's bookings. */
    private function roles(): void
    {
        lynx()->run(SaveRole::class, ['name' => 'staff', 'label' => 'Staff', 'grants' => [['permission' => 'rooms.book']]]);
        lynx()->run(SaveRole::class, ['name' => 'manager', 'label' => 'Office manager', 'grants' => [
            ['permission' => 'admin.access'], ['permission' => 'pages.view'], ['permission' => 'pages.edit'],
            ['permission' => 'rooms.book'], ['permission' => 'rooms.manage'],
        ]]);
    }

    private function fields(): void
    {
        // Rooms
        lynx()->fields->add('capacity', 'integer', label: 'Seats');
        lynx()->fields->add('floor', 'text', label: 'Floor');
        lynx()->fields->add('equipment', 'choice', label: 'Equipment', settings: ['multiple' => true, 'options' => [
            ['value' => 'screen', 'label' => 'Screen'],
            ['value' => 'video', 'label' => 'Video calls'],
            ['value' => 'whiteboard', 'label' => 'Whiteboard'],
        ]]);
        lynx()->fields->add('weekday', 'choice', label: 'Day', settings: ['options' => array_map(
            fn (string $day): array => ['value' => $day, 'label' => ucfirst($day)],
            self::WEEKDAYS,
        )]);
        lynx()->fields->add('opens', 'time', label: 'Opens');
        lynx()->fields->add('closes', 'time', label: 'Closes');
        lynx()->fields->add('opening_hours', 'repeater', label: 'Opening hours', settings: [
            'fields' => [['name' => 'weekday', 'required' => true], ['name' => 'opens', 'required' => true], ['name' => 'closes', 'required' => true]],
            'max' => 14,
            'item_label' => '{weekday} {opens} to {closes}',
            'add_label' => 'Add a day',
        ]);
        lynx()->fields->add('check_in', 'toggle', label: 'Meetings need a check in');
        lynx()->fields->add('color', 'text', label: 'Color on the screens');

        // Screens
        lynx()->fields->add('kind', 'choice', label: 'Kind', settings: ['input' => 'radios', 'options' => [
            ['value' => 'lobby', 'label' => 'Lobby: every room'],
            ['value' => 'door', 'label' => 'Door: one room'],
        ]]);
        lynx()->fields->add('room', 'page', label: 'Room', settings: ['multiple' => false, 'templates' => ['room'], 'input' => 'select']);
        lynx()->fields->add('rooms', 'page', label: 'Rooms', settings: ['templates' => ['room']]);
        lynx()->fields->add('show_announcements', 'toggle', label: 'Show announcements');
        lynx()->fields->add('screen_language', 'choice', label: 'Language on the screen', settings: ['input' => 'radios', 'options' => [
            ['value' => 'en', 'label' => 'English'],
            ['value' => 'fr', 'label' => 'Français'],
        ]]);

        // Announcements
        lynx()->fields->add('starts', 'date', label: 'Shows from', settings: ['time' => true]);
        lynx()->fields->add('ends', 'date', label: 'Shows until', settings: ['time' => true]);

        // Settings: the screens' look
        lynx()->fields->add('logo', 'images', label: 'Logo', settings: ['max_files' => 1]);
        lynx()->fields->add('accent_color', 'text', label: 'Accent color');
    }

    private function templates(): void
    {
        lynx()->templates->add('rooms', fields: [['name' => 'title', 'required' => true]], label: 'Rooms', singleton: true);
        lynx()->templates->add('room', fields: [
            ['name' => 'title', 'required' => true], ['name' => 'office', 'required' => true], 'photo', ['name' => 'capacity', 'required' => true], 'floor',
            'equipment', 'opening_hours', 'check_in', 'color',
        ], label: 'Room');

        lynx()->templates->add('screens', fields: [['name' => 'title', 'required' => true]], label: 'Screens', hasUrl: false, singleton: true);
        lynx()->templates->add('screen', fields: [
            ['name' => 'title', 'required' => true], ['name' => 'kind', 'required' => true], ['name' => 'office', 'required' => true], 'room', 'rooms', 'show_announcements', 'screen_language',
        ], label: 'Screen', hasUrl: false);

        lynx()->templates->add('announcements', fields: [['name' => 'title', 'required' => true]], label: 'Announcements', hasUrl: false, singleton: true);
        lynx()->templates->add('announcement', fields: [
            ['name' => 'title', 'required' => true], 'body', 'photo', 'starts', 'ends',
        ], label: 'Announcement', hasUrl: false);

        lynx()->templates->add('settings', fields: [['name' => 'title', 'required' => true], 'logo', 'accent_color'], label: 'Settings', hasUrl: false, singleton: true);

        // Where pages may go: the admin offers only these, and the API refuses the rest
        $children = [
            'home' => ['practice-areas', 'team', 'news', 'offices', 'contact', 'page', 'search', 'rooms', 'screens', 'announcements', 'settings'],
            'practice-areas' => ['practice-area'], 'practice-area' => [],
            'team' => ['lawyer'], 'lawyer' => [],
            'news' => ['post'], 'post' => [],
            'offices' => ['office'], 'office' => [],
            'contact' => [], 'page' => [], 'search' => [],
            'rooms' => ['room'], 'room' => [],
            'screens' => ['screen'], 'screen' => [],
            'announcements' => ['announcement'], 'announcement' => [],
        ];

        foreach ($children as $template => $allowed) {
            lynx()->templates->update($template, ['allow_children' => $allowed !== [], 'child_templates' => $allowed]);
        }

        foreach ([['rooms', 'Rooms', 'salles', 'Salles'], ['screens', 'Screens', null, null], ['announcements', 'Announcements', null, null], ['settings', 'Settings', null, null]] as [$template, $title, $frName, $frTitle]) {
            $page = lynx()->pages->new($template, '/');
            $page->name = $template;
            $page->title = $title;

            if ($frName !== null) {
                $page->setLanguageName('fr', $frName);
                $page->setLanguageValue('fr', 'title', $frTitle);
            }

            $page->save();
        }

        // One password for the Rooms section: it covers every room below it.
        lynx()->run(EnablePagePassword::class, ['template' => 'rooms']);
        $rooms = lynx()->pages->get('/rooms/') ?? throw new \RuntimeException('No Rooms page.');
        $rooms->{PagePasswordSchema::FIELD} = Value::string(config('rooms.section_password'));
        $rooms->save();
    }
}
