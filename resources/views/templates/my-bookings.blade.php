{{--
    The signed-in person's bookings, to move, rename or cancel, with their calendar feed and their
    AI assistant's token. A gate in RoomsServiceProvider keeps out people who may not book.
--}}
@php($book = lynx()->pages->find('template=book, include=hidden')->first())
<x-layout :title="(string) $page->title" robots="noindex, nofollow">
    <x-page-header :title="(string) $page->title" :summary="(string) $page->summary">
        @if ($book)
            <a href="{{ $book->url() }}" class="link-wave mt-5 font-medium text-lake hover:text-copper">{{ $book->title }}</a>
        @endif
    </x-page-header>
    <livewire:my-bookings :page-id="(int) $page->id" />
    <livewire:assistant-access />
</x-layout>
