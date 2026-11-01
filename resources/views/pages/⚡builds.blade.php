<?php

use App\Support\Releases;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Release history')] class extends Component
{
    public string $project = 'server';

    public function mount(string $project): void
    {
        abort_unless(in_array($project, ['server', 'proxy'], true), 404);

        $this->project = $project;
    }

    public function with(): array
    {
        $repo = config('basalt.repos.'.$this->project);

        return [
            'repo' => $repo,
            'releases' => Releases::history($repo),
            'name' => $this->project === 'proxy' ? 'Basalt Proxy' : 'Basalt Server',
            'accentText' => $this->project === 'proxy' ? 'text-warped' : 'text-magma',
        ];
    }
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-4xl pt-10 pb-16">
            <nav aria-label="Breadcrumb" class="mb-8 flex flex-wrap items-center gap-2 text-sm text-muted">
                <a href="{{ route('projects') }}" wire:navigate class="transition-colors hover:text-ink">Projects</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('projects.'.$project) }}" wire:navigate class="transition-colors hover:text-ink">{{ $name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-soft" aria-current="page">Releases</span>
            </nav>

            <h1 class="text-crystal text-[clamp(2rem,5vw,3rem)] leading-[1.08] font-semibold tracking-[-.03em]">Release history</h1>
            <p class="mt-4 text-lg text-soft">Every stable release of <span class="{{ $accentText }}">{{ $name }}</span>.</p>

            <div class="mt-10 grid gap-4">
                @forelse ($releases as $release)
                    <article class="glass p-5 sm:p-6" wire:key="release-{{ $release['tag'] }}">
                        <header class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <h2 class="font-mono text-2xl font-semibold text-ink">{{ $release['version'] }}</h2>
                            @if ($loop->first)
                                <span class="text-sm font-semibold {{ $accentText }}">Latest</span>
                            @endif
                            <span class="pill">Stable</span>
                            @if ($release['date'])
                                <time datetime="{{ $release['date'] }}" class="text-sm text-muted sm:ml-auto">{{ \Illuminate\Support\Carbon::parse($release['date'])->format('j M Y') }}</time>
                            @endif
                        </header>

                        @if ($release['assets'])
                            <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($release['assets'] as $asset)
                                    <a href="{{ $asset['url'] }}" class="flex items-center gap-3 rounded-lg border border-line bg-raised p-3 transition-colors hover:border-line-strong">
                                        <x-icon.brand :name="$asset['icon']" class="h-5 w-auto shrink-0 text-soft" />
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-ink">{{ $asset['label'] }}</span>
                                            <span class="block truncate text-xs text-muted">{{ $asset['arch'] }}@if ($asset['size']), {{ round($asset['size'] / 1048576) }} MB @endif</span>
                                        </span>
                                        <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 {{ $accentText }}" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
                            @if ($release['checksums'])
                                <a href="{{ $release['checksums'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-slate underline-offset-4 transition-colors hover:text-ink">SHA256SUMS</a>
                            @endif
                            @if ($release['url'])
                                <a href="{{ $release['url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-slate underline-offset-4 transition-colors hover:text-ink">Release on GitHub</a>
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="glass p-6">
                        <p class="text-soft">Release history is unavailable right now.</p>
                        <a href="{{ $repo }}/releases" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-sm text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">View releases on GitHub</a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
