<?php

use App\Support\TimingReports;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Timings report')] class extends Component
{
    public string $key = '';

    public function mount(string $key): void
    {
        abort_unless(TimingReports::validKey($key) && TimingReports::raw($key) !== null, 404);

        $this->key = $key;
    }
};
?>

<div class="px-3">
    <div class="mx-auto max-w-[96rem] pt-10 pb-16">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 px-1">
            <span class="pill">Timings report</span>
            <a href="{{ route('timings.download', ['key' => $key]) }}" class="btn btn-ghost px-4 py-2 text-sm" download>
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                Download JSON
            </a>
        </div>

        {{-- The viewer builds everything inside this node, so Livewire must leave it alone. --}}
        <div wire:ignore data-timings-viewer data-src="{{ route('timings.data', ['key' => $key]) }}">
            <p class="py-24 text-center text-sm text-muted">Loading the report...</p>
        </div>

        <p class="mt-12 px-1 text-xs text-muted">Reports are kept for {{ \App\Support\TimingReports::RETENTION_DAYS }} days. Anyone with the link can read this one.</p>
    </div>
</div>
