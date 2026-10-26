<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Admin\Branding\SaveBranding;
use LynxCms\Cache\Actions\SavePageCacheRules;
use LynxCms\Seo\Actions\EnableSeo;
use LynxCms\Seo\Actions\MapTemplate;
use LynxCms\Seo\SeoSchema;
use RuntimeException;

/**
 * What Lynx's modules do for the firm's site: the main and footer menus, the search page, SEO with
 * structured data for lawyers, offices and posts, and the page cache's rules. Runs after the site's
 * content, which the menus and the search page sit beside.
 */
final class SiteFeaturesSeeder extends Seeder
{
    /** The site's birch and ink (resources/css/app.css), in hex for the admin. */
    private const string BIRCH = '#f3eee4';

    private const string INK = '#2b1d17';

    /** Templates with SEO fields in the page editor: every public page of the site. */
    private const array SEO_TEMPLATES = ['home', 'practice-areas', 'practice-area', 'team', 'lawyer', 'news', 'post', 'offices', 'office', 'contact', 'page', 'search'];

    public function run(): void
    {
        $this->menus();
        $this->seo();
        $this->searchPage();
        $this->branding();

        // Search results depend on the query, so they are never stored; every other page is.
        lynx()->run(SavePageCacheRules::class, ['templates' => ['search' => ['cache' => false]]]);
    }

    /** The admin's sign-in screens and sidebar in the firm's wordmark and colours (Settings > General). */
    private function branding(): void
    {
        lynx()->run(SaveBranding::class, [
            'name' => 'Castor & Otters',
            'logo' => 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents(public_path('images/logo.svg'))),
            'background' => self::BIRCH,
            'accent' => self::INK,
        ]);
    }

    private function searchPage(): void
    {
        $page = lynx()->pages->new('search', '/');
        $page->name = 'search';
        $page->title = 'Search';
        $page->setLanguageName('fr', 'recherche');
        $page->setLanguageValue('fr', 'title', 'Recherche');
        // Results pages are not worth indexing; this also keeps the page out of the sitemap.
        $page->{SeoSchema::NOINDEX} = true;
        $page->save();
    }

    private function menus(): void
    {
        lynx()->menus()->create('main', ['en' => 'Main navigation', 'fr' => 'Navigation principale']);

        foreach (['/practice-areas/', '/team/', '/news/', '/offices/', '/contact/'] as $path) {
            lynx()->menus()->saveItem(['menu' => 'main', 'kind' => 'page', 'page' => $path]);
        }

        lynx()->menus()->create('footer', ['en' => 'Legal', 'fr' => 'Mentions']);

        foreach (['/legal/', '/privacy/'] as $path) {
            lynx()->menus()->saveItem(['menu' => 'footer', 'kind' => 'page', 'page' => $path]);
        }
    }

    private function seo(): void
    {
        foreach (self::SEO_TEMPLATES as $template) {
            lynx()->run(EnableSeo::class, ['template' => $template]);
        }

        $settings = lynx()->settings(SeoSchema::SETTINGS_TEMPLATE) ?? throw new RuntimeException('The SEO settings page is missing.');
        $settings->{SeoSchema::SITE_NAME} = 'Castor & Otters, Inc.';
        $settings->setLanguageValue('fr', SeoSchema::SITE_NAME, 'Castor & Otters, inc.');
        $settings->{SeoSchema::TITLE_PATTERN} = '{title} · {site}';
        $settings->{SeoSchema::DEFAULT_DESCRIPTION} = 'A wildlife law firm in Vancouver and Montreal, for every creature that keeps its feet wet.';
        $settings->setLanguageValue('fr', SeoSchema::DEFAULT_DESCRIPTION, 'Un cabinet de droit de la faune à Vancouver et à Montréal, pour toutes les créatures qui ont les pieds dans l’eau.');
        $settings->{SeoSchema::ORG_TYPE} = 'organization';
        $settings->{SeoSchema::ORG_NAME} = 'Castor & Otters, Inc.';
        $settings->save();

        // The home page's title is the firm's name, so the pattern would say it twice.
        $home = lynx()->pages->get('/') ?? throw new RuntimeException('The home page is missing.');
        $home->{SeoSchema::TITLE} = 'Castor & Otters, Inc. · Wildlife law in Vancouver and Montréal';
        $home->setLanguageValue('fr', SeoSchema::TITLE, 'Castor & Otters, inc. · Droit de la faune à Vancouver et à Montréal');
        $home->save();

        lynx()->run(MapTemplate::class, ['template' => 'lawyer', 'type' => 'Person', 'map' => [
            'name' => '@title',
            'jobTitle' => 'role',
            'description' => 'summary',
            'worksFor' => '@organization',
        ]]);

        lynx()->run(MapTemplate::class, ['template' => 'office', 'type' => 'ProfessionalService', 'map' => [
            'name' => '@title',
            'telephone' => 'phone',
            'description' => 'summary',
            'address.streetAddress' => 'address',
            'address.addressLocality' => 'city',
            'address.addressCountry' => '=CA',
        ]]);

        lynx()->run(MapTemplate::class, ['template' => 'post', 'type' => 'NewsArticle', 'map' => [
            'headline' => '@title',
            'description' => 'summary',
            'datePublished' => 'published_on',
            'dateModified' => '@modified',
            'author' => '@organization',
            'publisher' => '@organization',
        ]]);
    }
}
