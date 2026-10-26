@props(['page', 'width' => 480, 'eager' => false])
{{-- A page's picture: the photo an editor uploaded wins; otherwise the drawing its illustration field picks. --}}
{{-- The photo field takes one file, so it reads as one image (or null, and on templates without it). --}}
@php($photo = $page->photo)
<figure {{ $attributes->merge(['class' => 'overflow-hidden bg-sand']) }}>
    @if ($photo)
        <x-lynx-image :image="$photo" :width="$width" :height="(int) round($width * 0.8)" :lazy="! $eager" class="h-full w-full object-cover" />
    @else
        <x-illustration :name="$page->illustration?->value" :eager="$eager" class="h-full w-full object-cover" />
    @endif
</figure>
