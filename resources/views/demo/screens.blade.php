{{-- The public demo's screens: every lobby TV and door tablet, each opening live in its own tab. --}}
<x-layout title="Room screens">
    <section class="mx-auto max-w-3xl px-5 pb-8 pt-10 sm:px-8 sm:pt-16">
        <h1 class="text-title">Room screens</h1>
        <p class="mt-4 max-w-xl text-lg text-muted">A TV in each lobby and a tablet at each door, live. Book a room as Staff and it shows up here within a minute. Montreal's screens speak French.</p>
        @foreach ([true => 'Lobby TVs', false => 'Door tablets'] as $isLobby => $heading)
            <h2 class="mt-12 font-display text-2xl">{{ $heading }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach (array_filter($screens, fn (array $screen): bool => $screen['isLobby'] === (bool) $isLobby) as $screen)
                    <li><a href="{{ $screen['url'] }}" target="_blank" rel="noopener" class="link-wave" data-demo-screen="{{ $screen['url'] }}">{{ $screen['title'] }}</a></li>
                @endforeach
            </ul>
        @endforeach
    </section>
</x-layout>
