{{-- Booking a room, for signed-in staff. --}}
<x-layout :title="__('staff.book_title')" robots="noindex, nofollow">
    <x-page-header :title="__('staff.book_title')" :summary="__('staff.book_summary')">
        <a href="{{ route($language === 'fr' ? 'staff.mine.fr' : 'staff.mine') }}" class="link-wave mt-5 font-medium text-lake hover:text-copper">{{ __('staff.my_bookings') }}</a>
    </x-page-header>
    <livewire:book-a-room />
</x-layout>
