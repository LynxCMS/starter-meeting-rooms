<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;
use RuntimeException;

/**
 * The firm's words, in English and French: the team, the practice areas that name them, cases
 * and news, the sections' introductions and the legal pages. Content lives in content/*.php;
 * RoomsContentSeeder must run first, since lawyers belong to its offices.
 */
final class SiteContentSeeder extends Seeder
{
    private const string FRENCH = 'fr';

    public function run(): void
    {
        foreach ($this->content('team') as $lawyer) {
            $page = $this->page('lawyer', '/team/', $lawyer, $lawyer['name']);
            $page->role = $lawyer['role'];
            $page->illustration = $lawyer['illustration'];
            $page->office = [$this->get("/offices/{$lawyer['office']}/")->id];
            $page->save();
        }

        foreach ($this->content('practice-areas') as $area) {
            $page = $this->page('practice-area', '/practice-areas/', $area, $area['fr_name']);
            $page->illustration = $area['illustration'];
            $page->lawyers = array_map(fn (string $name): int => (int) $this->get("/team/{$name}/")->id, $area['lawyers']);
            $page->save();
        }

        foreach ($this->content('posts') as $post) {
            $page = $this->page('post', '/news/', $post, $post['fr_name']);
            $page->category = $post['category'];
            $page->published_on = $post['published_on'];
            $page->practice_areas = array_map(fn (string $name): int => (int) $this->get("/practice-areas/{$name}/")->id, $post['practice_areas']);
            $page->save();
        }

        foreach ($this->content('pages') as $path => $content) {
            $page = isset($content['new'])
                ? $this->page($content['new'], '/', ['name' => trim($path, '/')] + $content, $content['fr_name'])
                : $this->get($path);

            // Pages Lynx made, such as the page not found, get their French address here.
            if (! isset($content['new']) && isset($content['fr_name'])) {
                $page->setLanguageName(self::FRENCH, $content['fr_name']);
            }

            $this->write($page, $content);
            $page->save();
        }
    }

    /**
     * A new page under a parent, named in both languages and written in both.
     *
     * @param  array<string, mixed>  $content  name, and en and fr maps of field values
     */
    private function page(string $template, string $parent, array $content, string $frenchName): Page
    {
        $page = lynx()->pages->new($template, $parent);
        $page->name = $content['name'];
        $page->setLanguageName(self::FRENCH, $frenchName);
        $this->write($page, $content);

        return $page;
    }

    /** @param  array<string, mixed>  $content  en and fr maps of field values, and shared ones set once */
    private function write(Page $page, array $content): void
    {
        foreach ($content['shared'] ?? [] as $field => $value) {
            $page->{$field} = $value;
        }

        foreach ($content['en'] as $field => $value) {
            $page->{$field} = $value;
        }

        foreach ($content['fr'] as $field => $value) {
            $page->setLanguageValue(self::FRENCH, $field, $value);
        }
    }

    private function get(string $path): Page
    {
        return lynx()->pages->get($path) ?? throw new RuntimeException("SiteContentSeeder needs [{$path}]: run the schema and rooms seeders first.");
    }

    /** @return array<array-key, array<string, mixed>> */
    private function content(string $file): array
    {
        return require __DIR__."/content/{$file}.php";
    }
}
