{{-- A section's page that has no view of its own: its title and summary, then its pages. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <ul class="mx-auto max-w-6xl px-5 sm:px-8">
        @foreach ($page->children() as $child)
            <li class="wave-rule py-5"><a href="{{ $child->url() }}" class="font-display text-2xl hover:text-copper">{{ $child->title }}</a></li>
        @endforeach
    </ul>
</x-layout>
