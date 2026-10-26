{{-- The firm's logo for screens: the settings' logo when one is uploaded, else the wordmark with the beaver. --}}
@if ($logo)
    <img src="{{ $logo }}" alt="Castor & Otters, Inc." class="{{ $class ?? 'h-24' }} w-auto">
@else
    <div class="flex flex-col items-center gap-6 text-center">
        <img src="{{ asset('images/illustrations/beaver.svg') }}" alt="" class="{{ $class ?? 'h-56' }} w-auto rounded-[2.5rem]">
        <p class="font-display text-5xl">Castor <em class="text-copper-pale">&amp;</em> Otters</p>
    </div>
@endif
