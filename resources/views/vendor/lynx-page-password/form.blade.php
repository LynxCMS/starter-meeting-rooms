{{-- The password form of a locked page (the staff's rooms), in the site's frame, with the heron standing guard. --}}
<x-layout :page="$page" :title="__('lynx-page-password::form.title')" robots="noindex">
    <section class="mx-auto grid max-w-5xl items-center gap-12 px-5 pb-8 pt-10 sm:px-8 sm:pt-16 md:grid-cols-[1fr_1.1fr]">
        <x-illustration name="heron" :eager="true" :label="__('site.guard')" class="rise aspect-[5/4] w-full rounded-[2.5rem] object-cover" />

        <form method="POST" action="{{ route('lynx.page-password.unlock') }}" class="rise [animation-delay:120ms]">
            @csrf
            <input type="hidden" name="page" value="{{ $page->id }}">
            <input type="hidden" name="language" value="{{ $language }}">
            <p class="eyebrow text-copper">@string('password.eyebrow', ['en' => 'Staff', 'fr' => 'Personnel'], label: 'Password page: eyebrow', scope: 'site')</p>
            <h1 class="text-title mt-3">{{ __('lynx-page-password::form.title') }}</h1>
            <p class="mt-4 text-lg text-muted">{{ __('lynx-page-password::form.intro') }}</p>

            <label for="lynx-page-password" class="eyebrow mt-8 block text-ink">{{ __('lynx-page-password::form.password') }}</label>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input id="lynx-page-password" type="password" name="password" autocomplete="current-password" required autofocus
                       class="min-w-0 flex-1 rounded-full border border-ink/15 bg-paper px-5 py-3.5 text-lg outline-none transition-colors focus:border-copper aria-invalid:border-copper"
                       @error('password') aria-invalid="true" aria-describedby="lynx-page-password-error" @enderror>
                <button type="submit" class="rounded-full bg-ink px-7 py-3.5 font-medium text-paper transition-colors hover:bg-copper">{{ __('lynx-page-password::form.open') }}</button>
            </div>
            @error('password')
                <p id="lynx-page-password-error" role="alert" class="mt-3 font-medium text-copper">{{ $message }}</p>
            @enderror
        </form>
    </section>
</x-layout>
