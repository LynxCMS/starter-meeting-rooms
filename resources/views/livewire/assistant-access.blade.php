{{-- The person's token for an AI assistant (MCP), shown once with the ways to connect one. Hidden while the MCP server is off. --}}
<div class="mx-auto max-w-4xl px-5 sm:px-8">
    @if ($isOn)
        <section class="wave-rule mt-10 pt-8">
            <h2 class="font-display text-2xl">{{ __('staff.assistant_title') }}</h2>
            <p class="mt-2 max-w-2xl text-muted">{{ __('staff.assistant_intro') }}</p>
            @if ($token)
                @php
                    $command = "claude mcp add --transport http rooms {$endpoint} --header \"Authorization: Bearer {$token}\"";
                    $json = json_encode(['mcpServers' => ['rooms' => ['type' => 'http', 'url' => $endpoint, 'headers' => ['Authorization' => "Bearer {$token}"]]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                @endphp
                <div class="mt-4 grid gap-3 rounded-[1.5rem] bg-reed-pale p-5">
                    <p class="text-sm font-medium">{{ __('staff.assistant_once') }}</p>
                    @foreach ([$command, $json] as $snippet)
                        <div class="grid gap-2" x-data="{ copied: false }">
                            <pre class="overflow-x-auto whitespace-pre-wrap break-all rounded-xl bg-paper px-4 py-2 text-sm"><code>{{ $snippet }}</code></pre>
                            <button type="button" x-on:click="navigator.clipboard.writeText(@js($snippet)); copied = true" class="w-fit rounded-full bg-ink px-5 py-2 text-sm font-medium text-paper hover:bg-copper">
                                <span x-show="! copied">{{ __('staff.assistant_copy') }}</span><span x-show="copied" x-cloak>{{ __('staff.assistant_copied') }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @elseif ($current)
                <p class="mt-3 text-sm">
                    {{ __('staff.assistant_live', ['date' => $current->created_at?->locale(app()->getLocale())->isoFormat('LL')]) }}
                    @if ($current->last_used_at)
                        {{ __('staff.assistant_used', ['when' => $current->last_used_at->locale(app()->getLocale())->diffForHumans()]) }}
                    @endif
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" wire:click="issue" @if ($current) wire:confirm="{{ __('staff.assistant_replace_confirm') }}" @endif class="rounded-full border border-ink/15 px-5 py-2 text-sm hover:bg-birch">
                    {{ $current ? __('staff.assistant_replace') : __('staff.assistant_issue') }}
                </button>
                @if ($current)
                    <button type="button" wire:click="revoke" wire:confirm="{{ __('staff.assistant_revoke_confirm') }}" class="rounded-full border border-copper/40 px-5 py-2 text-sm text-copper hover:bg-copper-pale">{{ __('staff.assistant_revoke') }}</button>
                @endif
            </div>
        </section>
    @endif
</div>
