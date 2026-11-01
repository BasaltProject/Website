<?php

use App\Support\Docs;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    /** "page" is the large field on /docs, "sidebar" the compact one with a floating panel. */
    #[Locked]
    public string $variant = 'page';

    public string $query = '';

    public function mount(string $variant = 'page'): void
    {
        $this->variant = $variant;

        // Links such as /docs?q=term keep working without the field rewriting the URL while typing.
        if ($this->variant === 'page') {
            $this->query = (string) request()->query('q', '');
        }
    }

    #[Computed]
    public function results(): Collection
    {
        return Docs::search($this->query);
    }

    public function clear(): void
    {
        $this->query = '';
    }
};
?>

@php $sidebar = $variant === 'sidebar'; @endphp

<div class="relative" @if ($sidebar) x-on:click.outside="$wire.query !== '' && $wire.clear()" @endif>
    <div @class([
        'flex items-center border border-line bg-surface transition-colors focus-within:border-line-strong',
        'gap-2 rounded-lg px-3 py-2' => $sidebar,
        'glass gap-3 rounded-2xl px-5 py-4' => ! $sidebar,
    ])>
        <svg viewBox="0 0 16 16" @class(['shrink-0 text-muted', 'size-4' => $sidebar, 'size-[17px]' => ! $sidebar]) fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
            <circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/>
        </svg>

        <input
            type="search"
            data-docs-search
            wire:model.live.debounce.250ms="query"
            wire:keydown.escape="clear"
            placeholder="{{ $sidebar ? 'Search docs' : 'Search the documentation' }}"
            aria-label="Search the documentation"
            autocomplete="off"
            @class([
                'w-full bg-surface text-ink placeholder:text-muted focus:outline-none',
                'text-sm' => $sidebar,
                'text-[1.05rem]' => ! $sidebar,
            ])
        >

        <div wire:loading wire:target="query" class="shrink-0">
            <span class="block h-3.5 w-3.5 animate-spin rounded-full border-2 border-line border-t-soft"></span>
        </div>

        @if ($query !== '')
            <button type="button" wire:click="clear" wire:loading.remove wire:target="query"
                    class="shrink-0 rounded px-1.5 py-0.5 text-xs text-muted transition-colors hover:text-ink">
                Clear
            </button>
        @elseif ($sidebar)
            <kbd data-kbd class="hidden shrink-0 rounded border border-line bg-raised px-1.5 py-0.5 font-sans text-[.7rem] text-muted lg:inline">Ctrl K</kbd>
        @endif
    </div>

    @if (strlen(trim($query)) >= 2)
        <div wire:key="results-{{ $query }}" @class(['mt-4' => ! $sidebar, 'absolute top-full left-0 z-50 mt-2 w-full lg:w-[30rem]' => $sidebar])>
            @if ($this->results->isEmpty())
                <div @class(['px-6 py-8 text-center', 'glass rounded-2xl' => ! $sidebar, 'rounded-lg border border-line-strong bg-raised' => $sidebar])>
                    <p class="text-sm text-soft">Nothing matches "{{ $query }}" yet.</p>
                    <p class="mt-2 text-xs text-muted">Try a command, a file name or a setting.</p>
                </div>
            @else
                <ul @class([
                    'divide-y divide-line overflow-hidden',
                    'glass rounded-2xl' => ! $sidebar,
                    'max-h-[70dvh] overflow-y-auto rounded-lg border border-line-strong bg-raised' => $sidebar,
                ])>
                    @foreach ($this->results as $result)
                        <li>
                            <a href="{{ $result['url'] }}" wire:navigate @class(['block transition-colors', 'px-6 py-5 hover:bg-raised' => ! $sidebar, 'px-4 py-3 hover:bg-surface' => $sidebar])>
                                <p class="text-xs text-muted">{{ \App\Support\Docs::SECTIONS[$result['section']]['title'] }}</p>
                                <p class="mt-1 font-medium text-ink">{{ $result['title'] }}</p>
                                <p @class(['mt-1 text-sm leading-relaxed text-muted', 'line-clamp-2' => $sidebar])>{{ $result['excerpt'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
