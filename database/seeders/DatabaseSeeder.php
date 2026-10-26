<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use LynxCms\Core\Support\Value;

/**
 * Castor & Otters, Inc.: an owner account, Lynx installed with that owner as superuser, the
 * site's and the rooms' schema, two offices with their rooms and screens, the firm's content,
 * and a few bookings. php artisan migrate:fresh --seed starts over.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@meeting-rooms.example'],
            ['name' => 'Olga Owner', 'password' => Hash::make(Value::string(config('rooms.owner_password')))],
        );

        Artisan::call('lynx:install', ['--user' => (string) $owner->id]);

        $this->call([SiteSchemaSeeder::class, RoomsSchemaSeeder::class, RoomsContentSeeder::class, SiteContentSeeder::class, SiteFeaturesSeeder::class, BookingsSeeder::class]);
    }
}
