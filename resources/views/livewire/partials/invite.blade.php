{{-- The staff pages' "Invite" picker (InvitesPeople): a dropdown of colleagues and the people invited. --}}
<div class="flex flex-wrap items-center gap-2 text-sm">
    @if ($invitable !== [])
        <select wire:model.live="invitee" aria-label="{{ __('staff.invite') }}" class="rounded-full border border-ink/15 bg-paper px-4 py-2">
            <option value="">{{ __('staff.invite') }}</option>
            @foreach ($invitable as $person)
                <option value="{{ $person->email }}">{{ $person->name }}</option>
            @endforeach
        </select>
    @endif
    @foreach ($invited as $person)
        <span class="inline-flex items-center gap-1 rounded-full bg-mist py-1 pl-3 pr-1" wire:key="invited-{{ $person->id }}">
            {{ $person->name }}
            <button type="button" wire:click="uninvite('{{ $person->email }}')" class="grid size-6 place-items-center rounded-full hover:bg-shallows/50" aria-label="{{ __('staff.uninvite', ['name' => $person->name]) }}">×</button>
        </span>
    @endforeach
</div>
