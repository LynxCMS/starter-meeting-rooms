{{-- The team, by role in the field's order: founding partners first, paralegals last. --}}
<x-layout :page="$page" :title="(string) $page->title">
    <x-page-header :title="$page->title" :summary="$page->summary" />
    <div class="mx-auto max-w-6xl space-y-20 px-5 sm:px-8">
        @foreach (lynx()->fields->choices('role') as $role)
            @php($people = $page->children("role={$role->value}"))
            @if ($people->count())
                <section aria-labelledby="role-{{ $role->value }}">
                    <h2 id="role-{{ $role->value }}" class="eyebrow wave-rule pt-5 text-copper">@switch($role->value)
                            @case('founding-partner') @string('team.founding_partners', ['en' => 'Founding partners', 'fr' => 'Associés fondateurs'], label: 'Heading: founding partners') @break
                            @case('partner') @string('team.partners', ['en' => 'Partners', 'fr' => 'Associés'], label: 'Heading: partners') @break
                            @case('associate') @string('team.associates', ['en' => 'Associates', 'fr' => 'Avocats'], label: 'Heading: associates') @break
                            @case('paralegal') @string('team.paralegals', ['en' => 'Paralegals', 'fr' => 'Parajuristes'], label: 'Heading: paralegals') @break
                            @default {{ $role->label() }}
                        @endswitch</h2>
                    <div class="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($people as $lawyer)
                            <a href="{{ $lawyer->url() }}" class="group block">
                                <x-portrait :page="$lawyer" class="aspect-[5/4] rounded-[2rem] transition-transform duration-700 ease-out-quint group-hover:-translate-y-1" />
                                <h3 class="mt-5 font-display text-2xl group-hover:text-copper">{{ $lawyer->title }}</h3>
                                <p class="mt-1 text-sm italic text-muted">{{ $lawyer->species }} · {{ $lawyer->office?->city }}</p>
                                <p class="mt-3 leading-relaxed text-muted">{{ $lawyer->summary }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</x-layout>
