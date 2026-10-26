@props(['office', 'illustration' => 'lake'])
{{-- An office: a scene of its waters, then its city, its time and a line about it on deep lake blue. --}}
<a href="{{ $office->url() }}" {{ $attributes->merge(['class' => 'group block overflow-hidden rounded-[2rem] bg-lake text-paper']) }}>
    <span class="block overflow-hidden">
        <x-illustration :name="$illustration" class="aspect-[16/9] w-full object-cover transition-transform duration-700 ease-out-quint group-hover:scale-105" />
    </span>
    <span class="block p-8 sm:p-10">
        <span class="eyebrow block text-shallows">{{ $office->time_zone }}</span>
        <span class="mt-3 block font-display text-4xl">{{ $office->city }}</span>
        <span class="mt-3 block max-w-md leading-relaxed text-paper/80">{{ $office->summary }}</span>
    </span>
</a>
