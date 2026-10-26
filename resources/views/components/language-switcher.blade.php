@props(['page' => null])
{{-- The page in the other language: EN / FR, the current one marked. --}}
@if ($page && lynx()->languages->isMultilingual())
    <span class="flex items-center gap-1 text-sm" role="group" aria-label="{{ __('site.language') }}">
        @foreach (lynx()->languages->switcher($page) as $language)
            @if (! $loop->first)<span class="text-ink/25" aria-hidden="true">/</span>@endif
            <a href="{{ $language['url'] }}" hreflang="{{ $language['language']->appLocale() }}" lang="{{ $language['language']->appLocale() }}"
               @if ($language['isCurrent']) aria-current="true" @endif
               @class(['eyebrow rounded-full px-2 py-1 transition-colors', 'bg-ink text-paper' => $language['isCurrent'], 'hover:text-copper' => ! $language['isCurrent']])>{{ $language['language']->code }}</a>
        @endforeach
    </span>
@endif
