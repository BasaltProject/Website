<?php

use App\Support\TimingReports;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Timings report')] class extends Component
{
    public string $key = '';

    public function mount(string $key): void
    {
        abort_unless(TimingReports::validKey($key) && TimingReports::find($key) !== null, 404);

        $this->key = $key;
    }

    #[Computed]
    public function report(): array
    {
        return TimingReports::find($this->key) ?? abort(404);
    }
};
?>

@php
    use App\Support\TimingsFormat as Fmt;

    $report = $this->report;
    $server = $report['server'] ?? [];
    $ticks = $report['ticks'] ?? [];
    $perSecond = $ticks['per_second'] ?? [];
    $durations = $ticks['duration_millis'] ?? [];
    $points = $ticks['timeline']['points'] ?? [];
    $threads = $report['threads'] ?? [];
    $sections = collect($report['sections'] ?? [])->sortByDesc('total_millis')->values();
    $worlds = $server['worlds_detail'] ?? [];
    $platform = $report['platform'] ?? [];

    $tps = fn (string $window) => (float) ($perSecond[$window] ?? 0);
    $mspt = fn (string $window, string $field) => (float) ($durations[$window][$field] ?? 0);

    $chartMax = max(60, (int) ceil(collect($points)->max('max_millis') ?? 0));
    $chartWidth = 720;
    $chartHeight = 140;
    $count = max(count($points), 1);
    $step = $chartWidth / $count;
    $scale = fn (float $value) => $chartHeight - min($value / $chartMax, 1) * $chartHeight;
@endphp

<div class="px-4">
    <div class="mx-auto max-w-6xl pt-6 pb-16">
        <div class="reveal flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-3xl">
                <span class="pill">Timings report</span>
                <h1 class="text-crystal mt-6 text-[clamp(2rem,4.5vw,3rem)] leading-[1.08] font-semibold tracking-[-.03em]">
                    Basalt {{ $report['basalt'] ?? '' }}
                </h1>
                <p class="mt-4 text-white/50">
                    Profiled for {{ Fmt::duration((int) ($report['measured_seconds'] ?? 0)) }}
                    @if (isset($report['uptime_seconds']))
                        after {{ Fmt::duration((int) $report['uptime_seconds']) }} of uptime
                    @endif
                    @if (! empty($platform))
                        on {{ $platform['os'] ?? '' }} {{ $platform['arch'] ?? '' }}, {{ $platform['cores'] ?? '?' }} cores
                    @endif{{ '.' }}
                    Generated {{ $report['generated'] ?? '' }}.
                </p>
            </div>
            <a href="{{ route('timings.download', ['key' => $key]) }}" class="btn btn-ghost" download>
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                Download JSON
            </a>
        </div>

        <section class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">TPS</p>
                <p class="mt-3 font-mono text-3xl {{ Fmt::tpsTone($tps('1m')) }}">{{ number_format($tps('1m'), 1) }}</p>
                <p class="mt-2 font-mono text-xs text-white/45">
                    5s {{ number_format($tps('5s'), 1) }} / 1m {{ number_format($tps('1m'), 1) }} / 5m {{ number_format($tps('5m'), 1) }}
                </p>
            </div>
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">Tick duration</p>
                <p class="mt-3 font-mono text-3xl {{ Fmt::msptTone($mspt('1m', 'mean')) }}">{{ Fmt::millis($mspt('1m', 'mean')) }}</p>
                <p class="mt-2 font-mono text-xs text-white/45">
                    95% {{ Fmt::millis($mspt('1m', 'p95')) }} / max {{ Fmt::millis($mspt('1m', 'max')) }}
                </p>
            </div>
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">CPU</p>
                <p class="mt-3 font-mono text-3xl text-white/90">
                    {{ isset($server['cpu_percent_of_one_core']) ? number_format($server['cpu_percent_of_one_core'], 0).'%' : '-' }}
                </p>
                <p class="mt-2 font-mono text-xs text-white/45">of one core</p>
            </div>
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">Memory</p>
                <p class="mt-3 font-mono text-3xl text-white/90">{{ Fmt::bytes($server['process_resident_bytes'] ?? null) }}</p>
                <p class="mt-2 font-mono text-xs text-white/45">resident</p>
            </div>
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">Players</p>
                <p class="mt-3 font-mono text-3xl text-white/90">{{ $server['players_online'] ?? '-' }}<span class="text-lg text-white/35"> / {{ $server['players_max'] ?? '-' }}</span></p>
            </div>
            <div class="glass p-5">
                <p class="text-[.7rem] font-semibold tracking-[.16em] text-white/45 uppercase">Chunks loaded</p>
                <p class="mt-3 font-mono text-3xl text-white/90">{{ number_format($server['chunks_loaded'] ?? 0) }}</p>
                <p class="mt-2 font-mono text-xs text-white/45">{{ $server['worlds'] ?? '-' }} {{ ($server['worlds'] ?? 0) === 1 ? 'world' : 'worlds' }}</p>
            </div>
        </section>

        @if (count($points) > 1)
            <section class="mt-14">
                <h2 class="text-crystal text-2xl font-semibold tracking-[-.025em]">Tick duration over time</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/45">
                    One bar per second: the mean tick, with a mark at the slowest. A tick has 50 ms before the server falls behind.
                </p>
                <div class="glass mt-6 p-5">
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight + 4 }}" class="h-44 w-full" preserveAspectRatio="none" role="img" aria-label="Tick duration per second">
                        <line x1="0" x2="{{ $chartWidth }}" y1="{{ $scale(50) }}" y2="{{ $scale(50) }}" stroke="rgba(251,113,133,.5)" stroke-dasharray="4 4" vector-effect="non-scaling-stroke"/>
                        @foreach ($points as $i => $point)
                            @php
                                $mean = (float) ($point['mean_millis'] ?? 0);
                                $max = (float) ($point['max_millis'] ?? 0);
                                $x = $i * $step;
                            @endphp
                            @if (($point['ticks'] ?? 0) > 0)
                                <rect x="{{ number_format($x + $step * .12, 2, '.', '') }}" y="{{ number_format($scale($mean), 2, '.', '') }}" width="{{ number_format($step * .76, 2, '.', '') }}" height="{{ number_format($chartHeight - $scale($mean), 2, '.', '') }}" fill="{{ $mean > 50 ? 'rgba(251,113,133,.75)' : 'rgba(255,255,255,.4)' }}"/>
                                <rect x="{{ number_format($x + $step * .12, 2, '.', '') }}" y="{{ number_format($scale($max), 2, '.', '') }}" width="{{ number_format($step * .76, 2, '.', '') }}" height="1.5" fill="{{ $max > 50 ? 'rgb(251,113,133)' : 'rgba(255,255,255,.8)' }}"/>
                            @endif
                        @endforeach
                    </svg>
                    <div class="mt-2 flex justify-between font-mono text-[.7rem] text-white/35">
                        <span>{{ count($points) }}s ago</span>
                        <span>0 - {{ $chartMax }} ms</span>
                        <span>now</span>
                    </div>
                </div>
            </section>
        @endif

        @if (count($threads) > 0)
            <section class="mt-14" x-data="{ thread: 0 }">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-crystal text-2xl font-semibold tracking-[-.025em]">Where the time went</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/45">
                            Every section the server timed, nested the way it ran. A frame's percentage is its share of its thread. <span class="text-white/65">Self</span> is the time in the frame without what it called.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-ghost px-4 py-2 text-sm" x-on:click="$root.querySelectorAll('details').forEach(d => d.open = true)">Expand all</button>
                        <button type="button" class="btn btn-ghost px-4 py-2 text-sm" x-on:click="$root.querySelectorAll('details').forEach(d => d.open = false)">Collapse all</button>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($threads as $i => $thread)
                        <button type="button" x-on:click="thread = {{ $i }}"
                                x-bind:class="thread === {{ $i }} ? 'bg-white/12 text-white' : 'text-white/55 hover:text-white/85'"
                                class="rounded-full border border-white/10 px-4 py-1.5 text-sm transition-colors">
                            {{ $thread['name'] }}
                            <span class="ml-1 font-mono text-xs text-white/40">{{ Fmt::millis($thread['total_millis'] ?? 0) }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($threads as $i => $thread)
                    @php $threadTotal = (float) ($thread['root']['total_millis'] ?? 0); @endphp
                    <div x-show="thread === {{ $i }}" @if ($i > 0) x-cloak style="display: none" @endif class="glass mt-4 p-3">
                        <div class="hidden grid-cols-[minmax(0,1fr)_8rem_4rem_5.5rem_5.5rem_4.5rem] gap-x-3 px-2 pb-2 text-[.68rem] tracking-[.14em] text-white/35 uppercase sm:grid">
                            <span>Frame</span><span></span><span class="text-right">Share</span><span class="text-right">Total</span><span class="text-right">Self</span><span class="text-right">Calls</span>
                        </div>
                        @foreach ($thread['root']['children'] ?? [] as $child)
                            <x-timings.node :node="$child" :total="$threadTotal" :depth="0" />
                        @endforeach
                    </div>
                @endforeach
            </section>
        @endif

        @if (count($worlds) > 0)
            <section class="mt-14">
                <h2 class="text-crystal text-2xl font-semibold tracking-[-.025em]">Worlds</h2>
                <div class="glass mt-6 overflow-x-auto p-3">
                    <table class="w-full text-left font-mono text-sm">
                        <thead class="text-[.68rem] tracking-[.14em] text-white/35 uppercase">
                            <tr><th class="px-3 py-2 font-medium">World</th><th class="px-3 py-2 text-right font-medium">Chunks</th><th class="px-3 py-2 text-right font-medium">Entities</th><th class="px-3 py-2 text-right font-medium">Players</th></tr>
                        </thead>
                        <tbody class="text-white/80">
                            @foreach ($worlds as $world)
                                <tr class="border-t border-white/6">
                                    <td class="px-3 py-2">{{ $world['name'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($world['chunks'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($world['entities'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($world['players'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($sections->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-crystal text-2xl font-semibold tracking-[-.025em]">Sections</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/45">
                    The same measurements without the nesting, heaviest first: a section that runs from several places counts once here.
                </p>
                <div class="glass mt-6 overflow-x-auto p-3">
                    <table class="w-full text-left font-mono text-sm">
                        <thead class="text-[.68rem] tracking-[.14em] text-white/35 uppercase">
                            <tr><th class="px-3 py-2 font-medium">Section</th><th class="px-3 py-2 text-right font-medium">Total</th><th class="px-3 py-2 text-right font-medium">Average</th><th class="px-3 py-2 text-right font-medium">Max</th><th class="px-3 py-2 text-right font-medium">Calls</th></tr>
                        </thead>
                        <tbody class="text-white/80">
                            @foreach ($sections as $section)
                                <tr class="border-t border-white/6">
                                    <td class="px-3 py-2">{{ $section['name'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ Fmt::millis($section['total_millis'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right text-white/55">{{ Fmt::millis($section['average_millis'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right text-white/55">{{ Fmt::millis($section['max_millis'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right text-white/55">{{ number_format($section['calls'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <p class="mt-12 text-xs text-white/30">Reports are kept for {{ \App\Support\TimingReports::RETENTION_DAYS }} days. Anyone with the link can read this one.</p>
    </div>
</div>
