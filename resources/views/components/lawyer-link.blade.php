@props(['lawyer'])
{{-- A lawyer in a list: a small portrait, the name, and the role. --}}
<a href="{{ $lawyer->url() }}" {{ $attributes->merge(['class' => 'group flex items-center gap-4']) }}>
    <x-portrait :page="$lawyer" :width="96" class="size-14 shrink-0 rounded-full ring-1 ring-ink/10 transition-transform duration-500 ease-out-quint group-hover:-rotate-6" />
    <span>
        <span class="block font-display text-lg leading-tight group-hover:text-copper">{{ $lawyer->title }}</span>
        <span class="block text-sm text-muted"><x-job-title :lawyer="$lawyer" /></span>
    </span>
</a>
