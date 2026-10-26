@props(['page' => null, 'title' => null, 'robots' => null])
{{--
    The site's frame: the firm's name, the main menu, search, the language switcher, the page and
    the offices. Pages get their title, description, share tags, hreflang and structured data from
    Lynx SEO; a page of its own (the 404) gets a plain title.
--}}
@php
    $home = lynx()->pages->get('/');
    $search = lynx()->pages->get('/search/');
@endphp
<!DOCTYPE html>
<html lang="{{ lynx()->languages->current()->appLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if ($page && $robots)
        <x-lynx-seo :page="$page" :robots="$robots" />
    @elseif ($page)
        <x-lynx-seo :page="$page" />
    @else
        <title>{{ $title ? $title.' · ' : '' }}{{ $home?->title }}</title>
        @if ($robots)
            <meta name="robots" content="{{ $robots }}">
        @endif
    @endif
    <meta name="theme-color" content="#f8f4ec">
    @lynxCsrf
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper font-sans text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-paper">{{ __('site.skip') }}</a>

    <header class="relative z-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-6 sm:px-8">
            <a href="{{ $home?->url() }}" class="font-display text-xl leading-none tracking-tight sm:text-2xl">
                Castor <em class="text-copper">&amp;</em> Otters<span class="text-muted">, {{ lynx()->languages->current()->code === 'fr' ? 'inc.' : 'Inc.' }}</span>
            </a>

            <nav aria-label="{{ __('site.sections') }}" class="hidden items-center gap-7 text-[0.9375rem] lg:flex">
                <x-lynx-menu name="main" view="menus.navbar" />
                @if ($search)
                    <a href="{{ $search->url() }}" class="transition-colors hover:text-copper" title="{{ $search->title }}">
                        <x-search-icon class="size-5" /><span class="sr-only">{{ $search->title }}</span>
                    </a>
                @endif
                <x-language-switcher :page="$page" />
            </nav>

            <details class="group lg:hidden">
                <summary class="cursor-pointer list-none rounded-full border border-ink/15 px-4 py-2 text-sm [&::-webkit-details-marker]:hidden">{{ __('site.sections') }}</summary>
                <nav aria-label="{{ __('site.sections') }}" class="absolute inset-x-5 top-20 grid gap-1 rounded-3xl bg-paper p-4 shadow-[0_20px_60px_-20px_oklch(21%_0.022_35/0.35)] ring-1 ring-ink/10">
                    <x-lynx-menu name="main" view="menus.navbar" :stacked="true" />
                    @if ($search)
                        <a href="{{ $search->url() }}" class="flex items-center gap-2 rounded-2xl px-4 py-3 font-display text-lg hover:bg-birch"><x-search-icon class="size-5" /> {{ $search->title }}</a>
                    @endif
                    <div class="px-4 pt-2"><x-language-switcher :page="$page" /></div>
                </nav>
            </details>
        </div>
    </header>

    <main id="content" class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />
</body>
</html>
