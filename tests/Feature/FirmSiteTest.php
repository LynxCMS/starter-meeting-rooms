<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SiteSchemaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * Phase 5: Castor & Otters' pages in the firm's look, its drawings, and its content in English
 * and Canadian French. No test reads the site's wording: editors change it as Site and Page texts.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

it('draws no straight lines: rows are divided and sections edged by waves', function (): void {
    $this->get('/')->assertOk()->assertSee('wave-rule', false)->assertSee('link-wave', false)
        ->assertDontSee('border-t', false)->assertDontSee('underline', false);
});

it('shows every page of the site in both languages', function (string $en, string $fr): void {
    $this->get($en)->assertOk();
    $this->get($fr)->assertOk();
})->with([
    'home' => ['/', '/fr'],
    'practice areas' => ['/practice-areas', '/fr/domaines'],
    'a practice area' => ['/practice-areas/fisheries', '/fr/domaines/pecheries'],
    'team' => ['/team', '/fr/equipe'],
    'a lawyer' => ['/team/helene-heron', '/fr/equipe/helene-heron'],
    'news' => ['/news', '/fr/nouvelles'],
    'a post' => ['/news/catch-and-release', '/fr/nouvelles/remise-a-l-eau'],
    'offices' => ['/offices', '/fr/bureaux'],
    'an office' => ['/offices/montreal', '/fr/bureaux/montreal'],
    'contact' => ['/contact', '/fr/contact'],
    'search' => ['/search?q=beaver', '/fr/recherche?q=castor'],
    'legal notice' => ['/legal', '/fr/mentions-legales'],
    'privacy' => ['/privacy', '/fr/confidentialite'],
]);

it('lists the team by role, founding partners first and paralegals last', function (): void {
    $this->get('/team')->assertOk()->assertSeeInOrder(['Castor Bélanger', 'Hélène Héron', 'Pascale Brochet', 'Darcy Libellule']);
});

it('gives a lawyer their own title where the role\'s label does not fit, else the role', function (string $path, string $title): void {
    $this->get($path)->assertOk()->assertSee($title);
})->with([
    'French title of her own' => ['/fr/equipe/pascale-brochet', 'Avocate'],
    'French founder of her own' => ['/fr/equipe/olive-otter', 'Associée fondatrice'],
    'French role, no title of his own' => ['/fr/equipe/castor-belanger', 'Associé fondateur'],
    'English role, no title in English' => ['/team/pascale-brochet', 'Associate'],
]);

it('lists a practice area\'s lawyers with their titles', function (): void {
    $this->get('/fr/domaines/pecheries')->assertOk()->assertSeeInOrder(['Pascale Brochet', 'Avocate', 'Hélène Héron', 'Associée', 'Kit Martin', 'Avocate']);
});

it('finds a lawyer\'s practice areas from the areas that name them', function (): void {
    $this->get('/team/kit-martin')->assertOk()
        ->assertSee('Habitat and land use')->assertSee('Fisheries and catch disputes')
        ->assertDontSee('Water rights and dams');
});

it('shows who works in an office', function (): void {
    $this->get('/offices/vancouver')->assertOk()->assertSee('Castor Bélanger')->assertSee('Kit Martin')->assertDontSee('Oscar Otter');
});

it('dates posts in the visitor\'s language, newest first', function (): void {
    $this->get('/news')->assertOk()->assertSeeInOrder(['October 15, 2026', 'September 21, 2026', 'March 12, 2026']);
    $this->get('/fr/nouvelles')->assertOk()->assertSee('15 octobre 2026');
});

it('shows a page\'s illustration, and falls back to the lake', function (): void {
    $this->get('/team/castor-belanger')->assertOk()->assertSee('images/illustrations/beaver.svg', false);
    expect(view('components.illustration', ['name' => 'shark'])->render())->toContain('images/illustrations/lake.svg')
        ->and(view('components.illustration', ['name' => null])->render())->toContain('alt=""')
        ->and(view('components.illustration', ['name' => 'loon', 'label' => 'A loon'])->render())->toContain('alt="A loon"');
});

it('has a scalable picture for every illustration editors can pick', function (): void {
    foreach (array_keys(SiteSchemaSeeder::ILLUSTRATIONS) as $name) {
        expect(public_path("images/illustrations/{$name}.svg"))->toBeFile()
            ->and(file_get_contents(public_path("images/illustrations/{$name}.svg")))->toContain('viewBox="0 0 2304 1792"');
    }
});

it('shows an uploaded photo instead of the drawing', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('lynx/42/portrait.jpg', UploadedFile::fake()->image('portrait.jpg', 800, 640)->getContent());
    $olive = lynx()->pages->get('/team/olive-otter/');
    $olive->photo = [['values' => ['file' => '42/portrait.jpg', 'bytes' => 1000, 'mime' => 'image/jpeg', 'width' => 800, 'height' => 640]]];
    $olive->save();

    $this->get('/team/olive-otter')->assertOk()->assertSee('portrait', false)->assertDontSee('data-illustration="otter"', false);
});

it('links the staff\'s rooms from every page, behind their password', function (): void {
    $this->get('/privacy')->assertOk()->assertSee('/rooms"', false);
});

it('asks for the rooms\' password in the site\'s frame, kept out of search engines', function (): void {
    $this->get('/rooms')->assertForbidden()
        ->assertSee('images/illustrations/heron.svg', false)
        ->assertSee('<meta name="robots" content="noindex">', false)->assertSee('name="password"', false);
});

it('answers a missing page in the site\'s look', function (): void {
    $this->get('/this-page-swam-off')->assertNotFound()
        ->assertSee('images/illustrations/muskrat.svg', false);
});
