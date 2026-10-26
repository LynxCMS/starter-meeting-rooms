<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Bookings\BlockRoom;
use App\Actions\Bookings\BookRoom;
use App\Events\MeetingChanged;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Actions\Roles\AssignRoles;

/**
 * A few staff and an office manager, and meetings from the next working day: a weekly one with
 * colleagues invited, a private one, another, and a blocked time. Seeded meetings mail no one.
 */
final class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        Event::fakeFor(fn () => $this->meetings(), [MeetingChanged::class]);
    }

    private function meetings(): void
    {
        $ana = $this->person('ana@meeting-rooms.example', 'Ana Staff', 'staff');
        $mia = $this->person('mia@meeting-rooms.example', 'Mia Manager', 'manager');
        $this->person('oscar@meeting-rooms.example', 'Oscar Otter', 'staff');
        $this->person('pascale@meeting-rooms.example', 'Pascale Brochet', 'staff');
        $this->person('theo@meeting-rooms.example', 'Théo Tortue', 'staff');
        $day = CarbonImmutable::today()->addDay();

        while ($day->isWeekend()) {
            $day = $day->addDay();
        }

        $date = $day->toDateString();
        $book = fn (Actor $actor, array $input) => lynx()->actingAs($actor, fn () => lynx()->run(BookRoom::class, $input + ['date' => $date]));

        $book($ana, ['room' => '/rooms/lodge/', 'from' => '10:00', 'to' => '11:00', 'title' => 'Weekly planning', 'repeat' => 'weekly', 'until' => $day->addWeeks(8)->toDateString(),
            'attendees' => ['oscar@meeting-rooms.example', 'mia@meeting-rooms.example']]);
        $book($ana, ['room' => '/rooms/kingfisher/', 'from' => '14:00', 'to' => '15:30', 'title' => 'Salary review', 'private' => true]);
        $book($mia, ['room' => '/rooms/lodge/', 'from' => '15:00', 'to' => '16:00', 'title' => 'Supplier call']);
        lynx()->actingAs($mia, fn () => lynx()->run(BlockRoom::class, ['room' => '/rooms/saint-laurent/', 'date' => $date, 'from' => '09:00', 'to' => '12:00', 'reason' => 'New whiteboard']));
    }

    private function person(string $email, string $name, string $role): Actor
    {
        $user = User::query()->firstOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('password')]);
        lynx()->run(AssignRoles::class, ['user' => (string) $user->id, 'roles' => [$role]]);

        return Actor::user($user->id);
    }
}
