<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;

/**
 * The firm's two offices, two rooms in each with their hours, a lobby screen per office, a door
 * screen per room and the settings' look.
 */
final class RoomsContentSeeder extends Seeder
{
    private const array WEEK = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function run(): void
    {
        $vancouver = $this->office('vancouver', 'Vancouver', 'Vancouver', "1 Beaver Lodge Way\nVancouver, BC V6B 1A1", '+1 604 555 0142', 'pacific');
        $montreal = $this->office('montreal', 'Montreal', 'Montréal', "200, rue de la Loutre\nMontréal (Québec) H2Y 1C6", '+1 514 555 0187', 'eastern');

        $rooms = [
            ['lodge', 'The Lodge', $vancouver, 12, 'Ground floor', ['screen', 'video', 'whiteboard'], '#7c4a2d', $this->week('08:00', '20:00')],
            ['kingfisher', 'Kingfisher', $vancouver, 6, 'First floor', ['screen', 'video'], '#0e7490', $this->week('09:00', '18:00')],
            ['saint-laurent', 'Saint-Laurent', $montreal, 8, 'Second floor', ['whiteboard'], '#3f6212', $this->week('09:00', '18:00', friday: ['09:00', '13:00'])],
            ['huard', 'Huard', $montreal, 2, 'Second floor', ['video'], '#334155', $this->week('08:30', '17:30')],
        ];

        foreach ($rooms as [$name, $title, $office, $capacity, $floor, $equipment, $color, $hours]) {
            $page = lynx()->pages->new('room', '/rooms/');
            $page->office = [$office->id];
            $page->name = $name;
            $page->title = $title;
            $page->capacity = $capacity;
            $page->floor = $floor;
            $page->equipment = $equipment;
            $page->color = $color;
            $page->check_in = $capacity > 4;
            $page->opening_hours = $hours;
            $door = $page->save();
            $this->screen("{$title} door", 'door', $office, room: $door);
        }

        $this->screen('Vancouver lobby', 'lobby', $vancouver, announcements: true);
        $this->screen('Montreal lobby', 'lobby', $montreal, announcements: true);
        $this->announcement();

        $home = lynx()->pages->get('/') ?? throw new \RuntimeException('No home page.');
        $home->title = 'Castor & Otters, Inc.';
        $home->setLanguageValue('fr', 'title', 'Castor & Otters, inc.');
        $home->save();

        $settings = $this->section('settings');
        $settings->accent_color = '#0e7490';
        $settings->save();
    }

    /**
     * Opening hours on weekdays, with other hours on Friday when given.
     *
     * @param  array{0: string, 1: string}|null  $friday
     * @return list<array{values: array<string, string>}>
     */
    private function week(string $opens, string $closes, ?array $friday = null): array
    {
        return array_map(fn (string $day): array => ['values' => [
            'weekday' => $day,
            'opens' => $day === 'friday' && $friday !== null ? $friday[0] : $opens,
            'closes' => $day === 'friday' && $friday !== null ? $friday[1] : $closes,
        ]], self::WEEK);
    }

    private function office(string $name, string $title, string $city, string $address, string $phone, string $zone): Page
    {
        $office = lynx()->pages->new('office', '/offices/');
        $office->name = $name;
        $office->setLanguageName('fr', $name);
        $office->title = $title;
        $office->setLanguageValue('fr', 'title', $city);
        $office->city = $city;
        $office->address = $address;
        $office->phone = $phone;
        $office->time_zone = $zone;

        return $office->save();
    }

    private function screen(string $title, string $kind, Page $office, ?Page $room = null, bool $announcements = false): void
    {
        $screen = lynx()->pages->new('screen', $this->section('screens'));
        $screen->title = $title;
        $screen->kind = $kind;
        $screen->office = [$office->id];
        $screen->show_announcements = $announcements;
        // Montreal's screens speak French; Vancouver's English.
        $screen->screen_language = $office->name === 'montreal' ? 'fr' : 'en';

        if ($room instanceof Page) {
            $screen->room = [$room->id];
        }

        $screen->save();
    }

    /** One announcement for the lobbies, running for the coming weeks. */
    private function announcement(): void
    {
        $page = lynx()->pages->new('announcement', $this->section('announcements'));
        $page->title = 'The Lodge pond is cleaned on Friday';
        $page->setLanguageValue('fr', 'title', 'L’étang de The Lodge est nettoyé vendredi');
        $page->body = '<p>Meetings move upstairs. Waders are optional, opinions are not.</p>';
        $page->setLanguageValue('fr', 'body', '<p>Les réunions montent à l’étage. Les bottes-pantalons sont facultatives, les avis ne le sont pas.</p>');
        $page->starts = now()->startOfDay();
        $page->ends = now()->addWeeks(3);
        $page->save();
    }

    private function section(string $template): Page
    {
        return lynx()->pages->find("template={$template}, include=hidden")->first() ?? throw new \RuntimeException("Run RoomsSchemaSeeder first: no [{$template}] page.");
    }
}
