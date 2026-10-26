{{--
    A screen opened through its kiosk link: a lobby (every room of the office) or a door (one
    room). Dark and high contrast for TVs and tablets; resources/js/kiosk.js keeps it live.
--}}
@php
    $config = [
        'state' => $state,
        'words' => trans('screen', [], $language),
        'stateUrl' => route('kiosk.state', $token),
        'poll' => $poll,
        'reloadHour' => $reloadHour,
        'earlyMinutes' => (int) config('rooms.check_in_early_minutes'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $language }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $state['title'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/kiosk.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full cursor-none overflow-hidden bg-lake-deep font-sans text-paper antialiased select-none [&_button]:cursor-pointer"
      @if ($accent) style="--screen-accent: {{ $accent }}" @endif
      x-data="screen(@js($config))">

    <div x-show="offline" x-cloak class="fixed right-6 top-6 z-10 flex items-center gap-2 rounded-full bg-ink/80 px-4 py-2 text-lg">
        <span class="size-2.5 animate-pulse rounded-full bg-copper"></span> {{ __('screen.reconnecting', [], $language) }}
    </div>

    @if ($state['kind'] === 'door')
        @include('kiosk.door')
    @else
        @include('kiosk.lobby')
    @endif
</body>
</html>
