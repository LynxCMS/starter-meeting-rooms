{{-- Laravel's mail layout, signed by the firm rather than the app's name. --}}
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
Castor <em style="color: #b4602c;">&amp;</em> Otters
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} Castor & Otters, Inc. {{ __('mail.footer') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
