{{-- The signed-in person's bookings. --}}
<x-layout :title="__('staff.my_bookings')" robots="noindex, nofollow">
    <x-page-header :title="__('staff.my_bookings')" :summary="__('staff.mine_summary', ['name' => auth()->user()?->name])">
        <a href="{{ route($language === 'fr' ? 'staff.book.fr' : 'staff.book') }}" class="link-wave mt-5 font-medium text-lake hover:text-copper">{{ __('staff.book_title') }}</a>
    </x-page-header>
    <livewire:my-bookings />
    <livewire:assistant-access />
</x-layout>
