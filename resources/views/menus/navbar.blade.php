{{--
    A Lynx menu as the header's links: one line on wide screens, a stacked panel on phones
    ($attributes->get('stacked')). The current page and its section are marked in copper.
--}}
@php($stacked = (bool) $attributes->get('stacked'))
@foreach ($menu->items as $item)
    @if ($item->hasUrl())
        <a href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->rel) rel="{{ $item->rel }}" @endif
           @if ($item->isActive) aria-current="page" @endif
           @class([
               'transition-colors hover:text-copper',
               'rounded-2xl px-4 py-3 font-display text-lg hover:bg-birch' => $stacked,
               'text-copper' => $item->isActive || $item->isInActiveTrail,
           ])>{{ $item->label }}</a>
    @endif
@endforeach
