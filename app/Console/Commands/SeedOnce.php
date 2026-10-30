<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Seeds the demo firm when the site has no users yet, and otherwise does nothing, so the Docker
 * image can run it on every start without touching what editors changed.
 */
#[Signature('rooms:seed-once')]
#[Description('Seed the demo firm, unless the site already has it')]
final class SeedOnce extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->components->info('The site already has its firm. Nothing seeded.');

            return self::SUCCESS;
        }

        return $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
    }
}
