{{-- A plain page, such as the legal notices. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" />
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        <div class="copy">@field($page->body)</div>
    </div>
</x-layout>
