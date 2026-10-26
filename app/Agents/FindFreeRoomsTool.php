<?php

declare(strict_types=1);

namespace App\Agents;

use App\Bookings\Week;
use App\Models\Room;
use App\Rooms\FreeRooms;
use App\Rooms\Slots;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LynxCms\Core\Agents\AgentReadTool;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\Visibility\PageVisibility;
use LynxCms\Core\Support\Value;

/**
 * The rooms free for a meeting, for MCP clients: "a room for six with video tomorrow at 10" is one
 * call to this tool and one to rooms_book.
 */
final class FindFreeRoomsTool extends AgentReadTool
{
    private const int DEFAULT_MINUTES = 60;

    public static function name(): string
    {
        return 'find_free_rooms';
    }

    public static function description(): string
    {
        return 'Find meeting rooms free for a meeting on a day: long enough, with enough seats and the equipment asked for, optionally at a start time. '
            .'Returns each room that suits (path, office, capacity, equipment, today\'s date and time zone there) with the windows the meeting can start in: '
            .'any quarter hour from earliest to latest, keeping the gap between meetings. With a start time, rooms free then come first and the others suggest other times. '
            .'Then book with rooms_book (room path, date, from, and to = from plus the length), after a dry run.';
    }

    public static function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Day, as YYYY-MM-DD. Today in each office when left out.'),
            'minutes' => $schema->integer()->description('Length of the meeting in minutes. 60 when left out.'),
            'from' => $schema->string()->description('Start time wanted, as HH:MM, in the office\'s time zone.'),
            'seats' => $schema->integer()->description('How many people attend.'),
            'equipment' => $schema->array()->items($schema->string()->enum(FreeRooms::EQUIPMENT))->description('Equipment the room needs: screen, video (calls) or whiteboard.'),
            'office' => $schema->string()->description('Path or id of an office page, e.g. "/offices/montreal/". Every office when left out.'),
        ];
    }

    public static function permission(): string
    {
        return 'rooms.book';
    }

    public function handle(array $arguments): mixed
    {
        $validator = Validator::make($arguments, [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'minutes' => ['nullable', 'integer', 'min:'.Value::int(config('rooms.min_minutes'), 15), 'max:'.Value::int(config('rooms.max_minutes'), 240)],
            'from' => ['nullable', 'date_format:H:i'],
            'seats' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => [Rule::in(FreeRooms::EQUIPMENT)],
        ], ['equipment.*.in' => 'Equipment is one of: '.implode(', ', FreeRooms::EQUIPMENT).'.']);
        $offices = $this->offices($arguments['office'] ?? null);

        if ($validator->fails() || $offices === null) {
            return ['error' => $validator->errors()->first() ?: 'There is no office ['.Value::string($arguments['office'] ?? '').'].'];
        }

        $pages = [];

        foreach ($offices as $office) {
            array_push($pages, ...Week::rooms((int) $office->id)->all());
        }

        $minutes = Value::int($arguments['minutes'] ?? null, self::DEFAULT_MINUTES);
        $found = FreeRooms::among($pages, Value::string($arguments['date'] ?? '') ?: null, $minutes,
            Value::int($arguments['seats'] ?? null, 1), Value::strings($arguments['equipment'] ?? []));

        return ['minutes' => $minutes, ...$this->answer($found, Value::string($arguments['from'] ?? ''))];
    }

    /**
     * Rooms with a start time left; with $from, those free then apart from the others.
     *
     * @param  list<array{page: Page, room: Room, date: string, starts: list<string>}>  $found
     * @return array<string, list<array<string, mixed>>>
     */
    private function answer(array $found, string $from): array
    {
        $rooms = $others = [];

        foreach ($found as $item) {
            if ($item['starts'] === []) {
                continue;
            }

            $entry = [...RoomFacts::of($item['page'], $item['room']), 'date' => $item['date'], 'starts' => self::windows($item['starts'])];

            if ($from === '' || in_array($from, $item['starts'], true)) {
                $rooms[] = $entry;
            } else {
                $others[] = $entry;
            }
        }

        return $from === '' ? ['rooms' => $rooms] : ['rooms' => $rooms, 'other_times' => $others];
    }

    /**
     * Start times a quarter hour apart, as windows: ["09:00", "09:15", "09:30", "14:00"] is
     * 09:00 to 09:30 and 14:00 to 14:00. Fewer tokens for the model, same meaning.
     *
     * @param  list<string>  $starts
     * @return list<array{earliest: string, latest: string}>
     */
    private static function windows(array $starts): array
    {
        $windows = [];

        foreach ($starts as $start) {
            $last = array_key_last($windows);

            if ($last !== null && Slots::plus($windows[$last]['latest'], Slots::STEP_MINUTES) === $start) {
                $windows[$last]['latest'] = $start;
            } else {
                $windows[] = ['earliest' => $start, 'latest' => $start];
            }
        }

        return $windows;
    }

    /** @return list<Page>|null the office named, or every office; null when none has that name */
    private function offices(mixed $reference): ?array
    {
        if ($reference === null || $reference === '') {
            return Week::offices()->all();
        }

        $page = app(PageVisibility::class)->withoutRules(fn (): ?Page => lynx()->pages->get(is_numeric($reference) ? (int) $reference : Value::string($reference)));

        return $page instanceof Page && (string) $page->template()->name === 'office' ? [$page] : null;
    }
}
