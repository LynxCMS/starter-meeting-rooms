{{-- A Lynx menu as the footer's small links, such as the legal pages. --}}
@foreach ($menu->items as $item)
    @if ($item->hasUrl())
        <a href="{{ $item->url }}" class="hover:text-paper" @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
    @endif
@endforeach
