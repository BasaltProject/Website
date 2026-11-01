<?php

use App\Support\Docs;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'q', except: '')]
    public string $query = '';

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

<div class="relative">
    <div class="glass flex items-center gap-3 rounded-2xl px-5 py-4">
        <svg viewBox="0 0 16 16" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" class="shrink-0 text-muted" aria-hidden="true">
            <circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/>
        </svg>

        <input
            type="search"
            wire:model.live.debounce.250ms="query"
            placeholder="Search the documentation"
            aria-label="Search the documentation"
            autocomplete="off"
            class="w-full bg-surface text-[.98rem] text-ink placeholder:text-muted focus:outline-none"
        >

        <div wire:loading wire:target="query" class="shrink-0">
            <span class="block h-3.5 w-3.5 animate-spin rounded-full border-2 border-line border-t-soft"></span>
        </div>

        @if ($query !== '')
            <button type="button" wire:click="clear" wire:loading.remove wire:target="query"
                    class="shrink-0 rounded-full px-2 py-1 text-xs text-muted transition-colors hover:text-ink">
                Clear
            </button>
        @endif
    </div>

    @if (strlen(trim($query)) >= 2)
        <div class="mt-4" wire:key="results-{{ $query }}">
            @if ($this->results->isEmpty())
                <div class="glass rounded-2xl px-6 py-8 text-center">
                    <p class="text-sm text-soft">Nothing matches "{{ $query }}" yet.</p>
                    <p class="mt-2 text-xs text-muted">Try a command, a file name or a setting.</p>
                </div>
            @else
                <ul class="glass divide-y divide-line overflow-hidden rounded-2xl">
                    @foreach ($this->results as $result)
                        <li>
                            <a href="{{ $result['url'] }}" wire:navigate class="block px-6 py-5 transition-colors hover:bg-raised">
                                <div class="flex items-baseline gap-3">
                                    <span class="text-[.62rem] font-medium tracking-[.16em] text-muted uppercase">
                                        {{ \App\Support\Docs::SECTIONS[$result['section']]['title'] }}
                                    </span>
                                </div>
                                <p class="mt-1.5 font-medium text-ink">{{ $result['title'] }}</p>
                                <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $result['excerpt'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
