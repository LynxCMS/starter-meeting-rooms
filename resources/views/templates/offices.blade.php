{{-- Both offices, each with its address, its phone and its time. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <div class="mx-auto grid max-w-6xl gap-6 px-5 sm:px-8 md:grid-cols-2">
        @foreach ($page->children() as $office)
            <div>
                <x-office-card :office="$office" :illustration="$loop->first ? 'kingfisher' : 'loon'" />
                <div class="grid grid-cols-2 gap-6 px-2 pt-6 text-sm">
                    <address class="whitespace-pre-line not-italic leading-relaxed text-muted">{{ $office->address }}</address>
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $office->phone) }}" class="font-medium hover:text-copper">{{ $office->phone }}</a>
                </div>
            </div>
        @endforeach
    </div>
</x-layout>
