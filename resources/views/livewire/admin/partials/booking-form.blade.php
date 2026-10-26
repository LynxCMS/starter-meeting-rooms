{{--
    The form to book a room, block it, or change or cancel a booking ($form from EditsBookings).
    $rooms are the rooms it may move a booking to.
--}}
@php
    $isBlock = $form['mode'] === 'block';
    $isEdit = $form['booking'] !== null;
    $isSeries = $isEdit && $form['repeats'] !== null;
    $wholeSeries = $isSeries && $form['scope'] === 'series';
    $repeatLabels = ['weekly' => 'every week', 'biweekly' => 'every 2 weeks', 'monthly' => 'every month'];
    $heading = match (true) {
        $isBlock && $isEdit => 'Change blocked time',
        $isBlock => 'Block a room',
        $isEdit => 'Change booking',
        default => 'Book a room',
    };
@endphp
<form wire:submit="saveForm" class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-base font-semibold">{{ $heading }}</h2>
        <button type="button" wire:click="closeForm" class="text-sm text-muted-foreground hover:text-foreground">Close</button>
    </div>

    @if ($errors->any())
        <ul class="grid gap-1 rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    @if ($isSeries)
        <fieldset class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-md bg-muted px-3 py-2 text-sm">
            <legend class="sr-only">Change</legend>
            <span>Repeats {{ $repeatLabels[$form['repeats']] ?? $form['repeats'] }}.</span>
            <label class="flex items-center gap-2"><input type="radio" wire:model.live="form.scope" value="one"> Only {{ \Carbon\CarbonImmutable::parse($form['occurrence'])->format('D j M') }}</label>
            <label class="flex items-center gap-2"><input type="radio" wire:model.live="form.scope" value="series"> The whole series (time, title and privacy)</label>
        </fieldset>
    @endif

    <div class="grid gap-4 sm:grid-cols-4">
        <label class="grid gap-1.5 text-sm sm:col-span-2">
            <span class="font-medium">Room</span>
            <select wire:model="form.room" @disabled($wholeSeries) class="h-9 rounded-md border border-input bg-background px-3 disabled:opacity-50">
                @foreach ($rooms as $choice)
                    <option value="{{ $choice->id }}">{{ $choice->title }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5 text-sm sm:col-span-2">
            <span class="font-medium">Day</span>
            <input type="date" wire:model="form.date" @disabled($wholeSeries) class="h-9 rounded-md border border-input bg-background px-3 disabled:opacity-50">
        </label>
        <label class="grid gap-1.5 text-sm">
            <span class="font-medium">From</span>
            <input type="time" step="300" wire:model="form.from" class="h-9 rounded-md border border-input bg-background px-3 tabular-nums">
        </label>
        <label class="grid gap-1.5 text-sm">
            <span class="font-medium">To</span>
            <input type="time" step="300" wire:model="form.to" class="h-9 rounded-md border border-input bg-background px-3 tabular-nums">
        </label>
        <label class="grid gap-1.5 text-sm sm:col-span-2">
            <span class="font-medium">{{ $isBlock ? 'Reason' : 'Title' }}</span>
            <input type="text" wire:model="form.title" maxlength="120" placeholder="{{ $isBlock ? 'New screen, cleaning…' : 'What the meeting is' }}" class="h-9 rounded-md border border-input bg-background px-3">
        </label>
    </div>

    @if (! $isBlock && ! $isEdit)
        <div class="grid gap-4 sm:grid-cols-4">
            <label class="grid gap-1.5 text-sm sm:col-span-2">
                <span class="font-medium">Repeat</span>
                <select wire:model.live="form.repeat" class="h-9 rounded-md border border-input bg-background px-3">
                    <option value="">Does not repeat</option>
                    <option value="weekly">Every week</option>
                    <option value="biweekly">Every 2 weeks</option>
                    <option value="monthly">Every month, same weekday</option>
                </select>
            </label>
            @if ($form['repeat'] !== '')
                <label class="grid gap-1.5 text-sm sm:col-span-2">
                    <span class="font-medium">Until</span>
                    <input type="date" wire:model="form.until" class="h-9 rounded-md border border-input bg-background px-3">
                </label>
            @endif
        </div>
    @endif

    @if ($this->mayInvite())
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="font-medium">Invited</span>
            @if ($invitable !== [])
                <select wire:model.live="invitee" aria-label="Invite a colleague" class="h-9 rounded-md border border-input bg-background px-3">
                    <option value="">Invite a colleague…</option>
                    @foreach ($invitable as $person)
                        <option value="{{ $person->email }}">{{ $person->name }}</option>
                    @endforeach
                </select>
            @endif
            @foreach ($invited as $person)
                <span class="inline-flex items-center gap-1 rounded-md border bg-muted py-0.5 pl-2 pr-0.5" wire:key="invited-{{ $person->id }}">
                    {{ $person->name }}
                    <button type="button" wire:click="uninvite('{{ $person->email }}')" class="grid size-6 place-items-center rounded hover:bg-accent" aria-label="Take {{ $person->name }} off the invitation">×</button>
                </span>
            @endforeach
        </div>
    @elseif (! $isBlock && $invited !== [])
        <p class="text-sm text-muted-foreground">
            Invited: {{ collect($invited)->pluck('name')->join(', ') }}. Only the organizer changes who is invited{{ $isSeries ? ', for the whole series' : '' }}.
        </p>
    @endif

    @unless ($isBlock)
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="form.private" class="size-4 rounded border-input">
            Private: screens and other staff see only “Busy”
        </label>
    @endunless

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            {{ $isBlock ? ($isEdit ? 'Save blocked time' : 'Block') : ($isEdit ? 'Save changes' : 'Book') }}
        </button>
        @if ($isEdit)
            <button type="button" wire:click="cancelBooking" wire:confirm="{{ $isBlock ? 'Lift this blocked time?' : ($wholeSeries ? 'Cancel every meeting of this series?' : 'Cancel this booking? Its organizer and everyone invited are told.') }}"
                    class="inline-flex h-9 items-center rounded-md border border-destructive/40 px-4 text-sm font-medium text-destructive hover:bg-destructive/10">
                {{ $isBlock ? 'Lift blocked time' : ($wholeSeries ? 'Cancel series' : 'Cancel booking') }}
            </button>
        @endif
    </div>
</form>
