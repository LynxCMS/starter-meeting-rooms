<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Castor & Otters, Inc.'s website: English and Canadian French, and the fields and templates of
 * its practice areas, team, cases and news, offices, contact page and legal pages. The rooms'
 * schema (RoomsSchemaSeeder) comes after it.
 */
final class SiteSchemaSeeder extends Seeder
{
    /** Illustrations the site draws (resources/views/components/illustrations), by name. */
    public const array ILLUSTRATIONS = [
        'beaver' => 'Beaver', 'otter' => 'Otter', 'otter-bank' => 'Otter on the bank', 'heron' => 'Heron', 'pike' => 'Pike', 'loon' => 'Loon',
        'dragonfly' => 'Dragonfly', 'frog' => 'Frog', 'turtle' => 'Turtle', 'kingfisher' => 'Kingfisher',
        'muskrat' => 'Muskrat', 'lake' => 'Lake', 'reeds' => 'Reeds', 'dam' => 'Dam',
    ];

    /**
     * Office time zones, by the value the time zone field stores: Zap stores wall-clock times, so
     * each office reads them in its own zone (App\Models\Room::timeZone()).
     */
    public const array TIME_ZONES = [
        'pacific' => ['zone' => 'America/Vancouver', 'en' => 'Pacific time', 'fr' => 'heure du Pacifique'],
        'eastern' => ['zone' => 'America/Toronto', 'en' => 'Eastern time', 'fr' => 'heure de l’Est'],
    ];

    public function run(): void
    {
        lynx()->languages->update('default', ['code' => 'en', 'name' => 'English', 'locale' => 'en-CA']);
        lynx()->languages->add('fr', 'Français', locale: 'fr-CA', fallback: 'en');
        $this->fields();
        $this->templates();
    }

    private function fields(): void
    {
        // Text that visitors read in their language
        lynx()->fields->add('summary', 'textarea', translatable: true, label: 'Summary');
        lynx()->fields->add('body', 'textarea', translatable: true, label: 'Text', settings: ['format' => 'html']);
        lynx()->fields->add('illustration', 'choice', label: 'Illustration', settings: ['options' => array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::ILLUSTRATIONS),
            self::ILLUSTRATIONS,
        )]);

        lynx()->fields->add('photo', 'images', label: 'Photo', settings: ['max_files' => 1]);

        // The home page's hero: a catch phrase, and a few figures with a line each
        lynx()->fields->add('tagline', 'text', translatable: true, label: 'Catch phrase');
        lynx()->fields->add('figure', 'text', translatable: true, label: 'Figure');
        lynx()->fields->add('figure_note', 'text', translatable: true, label: 'What it counts');
        lynx()->fields->add('highlights', 'repeater', label: 'Figures', settings: [
            'fields' => [['name' => 'figure', 'required' => true], ['name' => 'figure_note', 'required' => true]],
            'max' => 3,
            'item_label' => '{figure} {figure_note}',
        ]);

        // The team
        lynx()->fields->add('role', 'choice', label: 'Role', settings: ['input' => 'radios', 'options' => [
            ['value' => 'founding-partner', 'label' => ['en' => 'Founding partner', 'fr' => 'Associé fondateur']],
            ['value' => 'partner', 'label' => ['en' => 'Partner', 'fr' => 'Associé']],
            ['value' => 'associate', 'label' => ['en' => 'Associate', 'fr' => 'Avocat']],
            ['value' => 'paralegal', 'label' => ['en' => 'Paralegal', 'fr' => 'Parajuriste']],
        ]]);
        // A role's label is one word per language; French titles agree with the person ("Avocate"),
        // so a lawyer may have a title of their own, shown instead of the role. The role still groups the team.
        lynx()->fields->add('job_title', 'text', translatable: true, label: 'Job title');
        lynx()->fields->add('species', 'text', translatable: true, label: 'Species');
        lynx()->fields->add('fun_fact', 'textarea', translatable: true, label: 'Fun fact');
        lynx()->fields->add('practice_areas', 'page', label: 'Practice areas', settings: ['templates' => ['practice-area']]);
        lynx()->fields->add('lawyers', 'page', label: 'Lawyers', settings: ['templates' => ['lawyer']]);

        // Offices
        lynx()->fields->add('city', 'text', label: 'City');
        lynx()->fields->add('address', 'textarea', label: 'Address');
        lynx()->fields->add('phone', 'text', label: 'Phone');
        lynx()->fields->add('time_zone', 'choice', label: 'Time zone', settings: ['input' => 'radios', 'options' => array_map(
            fn (string $value, array $zone): array => ['value' => $value, 'label' => ['en' => $zone['en'], 'fr' => $zone['fr']]],
            array_keys(self::TIME_ZONES),
            self::TIME_ZONES,
        )]);
        lynx()->fields->add('office', 'page', label: 'Office', settings: ['multiple' => false, 'templates' => ['office'], 'input' => 'select']);

        // Cases and news
        lynx()->fields->add('category', 'choice', label: 'Category', settings: ['input' => 'radios', 'options' => [
            ['value' => 'case', 'label' => ['en' => 'Case', 'fr' => 'Dossier']],
            ['value' => 'news', 'label' => ['en' => 'News', 'fr' => 'Nouvelle']],
        ]]);
        lynx()->fields->add('published_on', 'date', label: 'Published on');
    }

    private function templates(): void
    {
        $title = ['name' => 'title', 'required' => true];
        // Words visitors search for: summaries, texts and the lawyers' species and fun facts
        $searchable = fn (string $name, bool $required = false): array => ['name' => $name, 'searchable' => true, 'required' => $required];

        lynx()->templates->update('home', ['add' => ['summary', 'tagline', 'body', 'highlights']]);
        // The page lynx:install made for addresses that match nothing: a line of text and a drawing.
        lynx()->templates->update('not-found', ['add' => ['summary', 'illustration']]);
        lynx()->templates->add('practice-areas', fields: [$title, 'summary'], label: 'Practice areas', singleton: true);
        lynx()->templates->add('practice-area', fields: [$title, $searchable('summary', required: true), $searchable('body'), 'illustration', 'lawyers'], label: 'Practice area');
        lynx()->templates->add('team', fields: [$title, 'summary'], label: 'Team', singleton: true);
        lynx()->templates->add('lawyer', fields: [
            $title, ['name' => 'role', 'required' => true], 'job_title', $searchable('species'), 'illustration', 'photo', $searchable('summary'), $searchable('body'), $searchable('fun_fact'), 'office',
        ], label: 'Lawyer'); // practice areas name their lawyers, so a lawyer's areas are found from them
        lynx()->templates->add('news', fields: [$title, 'summary'], label: 'Cases and news', singleton: true);
        lynx()->templates->add('post', fields: [$title, ['name' => 'category', 'required' => true], ['name' => 'published_on', 'required' => true], $searchable('summary'), $searchable('body'), 'practice_areas'], label: 'Post');
        lynx()->templates->add('offices', fields: [$title, 'summary'], label: 'Offices', singleton: true);
        lynx()->templates->add('office', fields: [$title, ['name' => 'city', 'required' => true], 'address', 'phone', ['name' => 'time_zone', 'required' => true], $searchable('summary')], label: 'Office');
        lynx()->templates->add('contact', fields: [$title, 'summary', 'body'], label: 'Contact', singleton: true);
        lynx()->templates->add('page', fields: [$title, $searchable('body')], label: 'Page');
        lynx()->templates->add('search', fields: [$title], label: 'Search', singleton: true);

        $sections = [
            ['practice-areas', 'Practice areas', 'domaines', 'Domaines de pratique'],
            ['team', 'Our team', 'equipe', 'Notre équipe'],
            ['news', 'Cases and news', 'nouvelles', 'Dossiers et nouvelles'],
            ['offices', 'Offices', 'bureaux', 'Bureaux'],
            ['contact', 'Contact', 'contact', 'Contact'],
        ];

        foreach ($sections as [$template, $title, $frName, $frTitle]) {
            $page = lynx()->pages->new($template, '/');
            $page->name = $template;
            $page->title = $title;
            $page->setLanguageName('fr', $frName);
            $page->setLanguageValue('fr', 'title', $frTitle);
            $page->save();
        }
    }
}
