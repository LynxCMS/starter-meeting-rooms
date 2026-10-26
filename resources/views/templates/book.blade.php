{{--
    Booking a room, for signed-in staff: a gate in RoomsServiceProvider keeps others out. The title
    and summary are the page's; why the rules refuse a booking is in its Texts (refusal.*).
--}}
@php($mine = lynx()->pages->find('template=my-bookings, include=hidden')->first())
<x-layout :title="(string) $page->title" robots="noindex, nofollow">
    <x-page-header :title="(string) $page->title" :summary="(string) $page->summary">
        @if ($mine)
            <a href="{{ $mine->url() }}" class="link-wave mt-5 font-medium text-lake hover:text-copper">{{ $mine->title }}</a>
        @endif
    </x-page-header>
    <livewire:book-a-room :page-id="(int) $page->id" />
</x-layout>
