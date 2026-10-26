{{-- Cases and news, newest first, as a docket. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        @foreach ($page->children('sort=-published_on') as $post)
            <x-post-row :post="$post" />
        @endforeach
    </div>
</x-layout>
