{{-- The admin's Bookings screen: every room of an office, week by week. --}}
<x-lynx-admin::layout title="Bookings" :crumbs="['Pages' => route('lynx.admin.pages')]">
    <x-lynx-admin::ui.page-header title="Bookings" description="Each office's rooms, week by week. Book, block, change or cancel from here." />
    <livewire:admin.rooms-week />
</x-lynx-admin::layout>
