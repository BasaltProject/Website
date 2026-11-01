@props(['node', 'share', 'width', 'depth', 'branch'])

@php
    use App\Support\TimingsFormat;

    $heat = $share >= 0.25 ? 'bg-rose-400/70' : ($share >= 0.08 ? 'bg-amber-300/60' : 'bg-white/30');
@endphp

<div class="grid grid-cols-[minmax(0,1fr)_4.5rem] items-center gap-x-3 rounded-lg px-2 py-1.5 hover:bg-white/5 sm:grid-cols-[minmax(0,1fr)_8rem_4rem_5.5rem_5.5rem_4.5rem]">
    <div class="flex min-w-0 items-center gap-2">
        @if ($branch)
            <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="timings-caret shrink-0 text-white/40" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
        @else
            <span class="w-3 shrink-0"></span>
        @endif
        <span class="truncate font-mono text-[.8rem] text-white/85" title="{{ $node['name'] }}">{{ $node['name'] }}</span>
    </div>
    <div class="hidden h-1.5 overflow-hidden rounded-full bg-white/8 sm:block" aria-hidden="true">
        <div class="h-full rounded-full {{ $heat }}" style="width: {{ number_format($width, 2, '.', '') }}%"></div>
    </div>
    <span class="text-right font-mono text-[.78rem] text-white/80">{{ number_format($share * 100, $share >= 0.1 ? 1 : 2) }}%</span>
    <span class="hidden text-right font-mono text-[.78rem] text-white/55 sm:block">{{ TimingsFormat::millis($node['total_millis'] ?? 0) }}</span>
    <span class="hidden text-right font-mono text-[.78rem] text-white/40 sm:block" title="Time in this frame itself, without what it called">{{ TimingsFormat::millis($node['self_millis'] ?? 0) }}</span>
    <span class="hidden text-right font-mono text-[.78rem] text-white/40 sm:block">{{ number_format($node['calls'] ?? 0) }}x</span>
</div>
