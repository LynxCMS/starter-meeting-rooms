{{-- The foot of every page: under the waterline, both offices, the footer menu (legal pages) and the staff's rooms. --}}
@php
    $offices = lynx()->pages->find('template=office, sort=sort');
    // The rooms sit behind a password, which hides them from menus; their link leads to its form.
    $rooms = app(\LynxCms\Core\Pages\Visibility\PageVisibility::class)->withoutRules(fn () => lynx()->pages->get('/rooms/'));
    // Signed-in staff get their booking pages instead, in the page's language.
    $canBook = auth()->check() && app(\LynxCms\Core\Permissions\Authorizer::class)->allows(\LynxCms\Contracts\Actions\Actor::user(auth()->id()), 'rooms.book');
    $fr = lynx()->languages->current()->code === 'fr';
@endphp
<footer class="mt-24 bg-lake-deep text-paper">
    <x-wave :flowing="true" class="-translate-y-px fill-paper" />
    <div class="mx-auto grid max-w-6xl gap-12 px-5 pb-14 pt-10 sm:px-8 md:grid-cols-[1.3fr_1fr_1fr]">
        <div>
            <p class="font-display text-2xl">Castor <em class="text-copper-pale">&amp;</em> Otters</p>
            <p class="mt-3 max-w-xs text-paper/70">@string('footer.tagline', ['en' => 'Wildlife law since the last ice age, give or take.', 'fr' => 'Droit de la faune depuis la dernière glaciation, à peu près.'], label: 'Footer tagline', scope: 'site')</p>
        </div>
        @foreach ($offices as $office)
            <div>
                <p class="eyebrow text-shallows">{{ $office->city }}</p>
                <address class="mt-3 whitespace-pre-line not-italic leading-relaxed text-paper/80">{{ $office->address }}</address>
                <a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $office->phone) }}" class="mt-2 link-wave text-paper transition-colors hover:text-copper-pale">{{ $office->phone }}</a>
            </div>
        @endforeach
    </div>
    <div class="wave-rule-light">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-6 text-sm text-paper/60 sm:px-8">
            <p>© {{ now()->year }} @string('footer.copyright', ['en' => 'Castor & Otters, Inc. Not your lawyers until we say so, in writing.', 'fr' => 'Castor & Otters, inc. Pas encore vos avocats, sauf par écrit.'], label: 'Footer copyright, after the year', scope: 'site')</p>
            <nav class="flex gap-5">
                <x-lynx-menu name="footer" view="menus.footer" />
                @if ($canBook)
                    <a href="{{ route($fr ? 'staff.book.fr' : 'staff.book') }}" class="hover:text-paper">{{ __('staff.book_title') }}</a>
                    <a href="{{ route($fr ? 'staff.mine.fr' : 'staff.mine') }}" class="hover:text-paper">{{ __('staff.my_bookings') }}</a>
                    <form method="POST" action="{{ route('staff.sign-out') }}" class="inline">
                        @csrf
                        <input type="hidden" name="language" value="{{ $fr ? 'fr' : 'en' }}">
                        <button type="submit" class="hover:text-paper">{{ __('staff.sign_out') }}</button>
                    </form>
                @elseif ($rooms)
                    <a href="{{ $rooms->url() }}" class="hover:text-paper">@string('footer.staff', ['en' => 'Staff', 'fr' => 'Personnel'], label: 'Footer link to the rooms', scope: 'site')</a>
                @endif
            </nav>
        </div>
    </div>
</footer>
