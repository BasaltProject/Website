@props(['repo', 'accent' => 'crimson', 'project' => null])

@php
    $release = \App\Support\Releases::latest($repo);
    $platforms = array_values(array_filter(config('basalt.platforms'), fn ($p) => ! isset($p['projects']) || in_array($project, $p['projects'], true)));
    $accentText = $accent === 'warped' ? 'text-warped' : 'text-crimson';
    $accentButton = $accent === 'warped' ? 'btn-warped' : 'btn-primary';
@endphp

{{-- Suggested download. Hidden until Alpine has picked a platform, so without JS
     (or on a system with no build) only the grid below shows. Detection can be
     wrong, so the button names the platform it chose. --}}
<div x-data="{
        target: null,
        platforms: @js(array_column($platforms, 'target')),
        pick(os, arch) {
            const target = os === 'macOS' ? (arch === 'x86' ? 'macOS-x64' : 'macOS-arm64')
                : os + (os === 'Linux' && arch === 'arm' ? '-arm64' : '-x64');
            return this.platforms.includes(target) ? target : null;
        },
        init() {
            const uad = navigator.userAgentData;
            const ua = navigator.userAgent;
            const hint = uad && uad.platform ? uad.platform : (navigator.platform || '');
            let os = null;

            if (/android|iphone|ipad|ipod|cros/i.test(ua) || (/mac/i.test(hint) && navigator.maxTouchPoints > 1)) os = null;
            else if (/win/i.test(hint)) os = 'Windows';
            else if (/mac/i.test(hint)) os = 'macOS';
            else if (/linux|x11/i.test(hint) || /linux|x11/i.test(ua)) os = 'Linux';

            if (! os) return;
            this.target = this.pick(os, null);

            if (uad && uad.getHighEntropyValues) {
                uad.getHighEntropyValues(['architecture']).then((v) => {
                    this.target = this.pick(os, v.architecture) ?? this.target;
                }).catch(() => {});
            }
        },
    }" x-cloak x-show="target" class="mb-6">
    @foreach ($platforms as $platform)
        <div x-show="target === '{{ $platform['target'] }}'" x-cloak>
            <a href="{{ $release['assets'][$platform['target']] ?? $repo.'/releases/latest' }}"
               class="btn {{ $accentButton }} w-full justify-start gap-4 px-7 py-5 text-base sm:w-auto">
                <x-icon.brand :name="$platform['icon']" class="h-7 w-auto shrink-0" />
                <span class="text-left">
                    <span class="block text-lg leading-tight">Download for {{ $platform['label'] }}</span>
                    <span class="mt-0.5 block text-sm font-medium">{{ $platform['arch'] }}@if ($release), version {{ $release['version'] }}@endif</span>
                </span>
                <svg viewBox="0 0 16 16" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="ml-2 shrink-0" aria-hidden="true">
                    <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
                </svg>
            </a>
            <p class="mt-3 text-sm text-muted">Detected from your browser and it can be wrong. Pick another platform below if so.</p>
        </div>
    @endforeach
</div>

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($platforms as $index => $platform)
        <a href="{{ $release['assets'][$platform['target']] ?? $repo.'/releases/latest' }}"
           class="glass card flex items-center gap-4 p-5">
            <x-icon.brand :name="$platform['icon']" class="h-6 w-auto shrink-0 text-soft" />
            <div class="min-w-0">
                <p class="font-medium text-ink">{{ $platform['label'] }}</p>
                <p class="truncate text-sm text-muted">{{ $platform['arch'] }}</p>
            </div>
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 {{ $accentText }}" aria-hidden="true">
                <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
            </svg>
        </a>
    @endforeach
</div>

@if ($project)
    @php $eggUpdated = \App\Support\Eggs::updated($project); @endphp
    <div class="glass mt-3 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h3 class="flex items-center gap-2 font-medium text-ink">
                <x-icon.pterodactyl class="h-auto w-6 shrink-0 text-soft" />
                Pterodactyl egg
            </h3>
            <p class="mt-1 text-sm text-muted">Install Basalt {{ ucfirst($project) }} from a Pterodactyl panel.@if ($eggUpdated) Updated {{ \Illuminate\Support\Carbon::parse($eggUpdated)->format('j M Y') }}.@endif</p>
        </div>
        <a href="{{ route('projects.egg', $project) }}" download class="btn btn-ghost shrink-0 justify-center">
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 {{ $accentText }}" aria-hidden="true">
                <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
            </svg>
            Download egg
        </a>
    </div>
@endif

@if ($project && config('basalt.docker.published') && config('basalt.docker.images.'.$project))
    @php
        $image = config('basalt.docker.images.'.$project);
        $dockerRun = $project === 'server'
            ? 'docker run -it -p 25565:25565 -p 19132:19132/udp -v basalt-server:/data -e EULA=1 '.$image.':latest'
            : 'docker run -it -p 25565:25565 -v basalt-proxy:/data '.$image.':latest';
    @endphp
    <div class="glass mt-3 min-w-0 p-5">
        <h3 class="flex items-center gap-2 font-medium text-ink">
            <x-icon.docker class="h-auto w-6 shrink-0 text-soft" />
            Docker
        </h3>
        <p class="mt-1 text-sm text-muted">Linux images for amd64 and arm64.</p>
        <x-code lang="bash">{{ $dockerRun }}</x-code>
        @if ($project === 'server')
            <p class="text-sm text-muted">EULA=1 accepts the <a href="https://www.minecraft.net/en-us/eula" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">Minecraft EULA</a>.</p>
        @endif
    </div>
@endif

<p class="mt-5 text-sm text-muted">
    @if ($release)
        Version {{ $release['version'] }}.
    @endif
    @if ($project)
        <a href="{{ route('projects.builds', ['project' => $project]) }}" wire:navigate class="font-medium {{ $accentText }} underline underline-offset-4 transition-colors">All releases</a>.
    @endif
    Each file is the executable itself, with a SHA256SUMS file beside them. A
    <a href="{{ $repo }}/releases/tag/nightly" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">nightly build</a>
    follows every change, and the
    <a href="{{ $repo }}/releases" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">releases page</a>
    has the rest.
</p>
