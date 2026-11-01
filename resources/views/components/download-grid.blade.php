@props(['repo', 'accent' => 'magma'])

@php
    $release = \App\Support\Releases::latest($repo);
    $platforms = config('basalt.platforms');
    $accentText = $accent === 'warped' ? 'text-warped' : 'text-magma';
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

<p class="mt-5 text-sm text-muted">
    @if ($release)
        Version {{ $release['version'] }}.
    @endif
    Each file is the executable itself, with a SHA256SUMS file beside them. A
    <a href="{{ $repo }}/releases/tag/nightly" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">nightly build</a>
    follows every change, and the
    <a href="{{ $repo }}/releases" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-slate underline-offset-4 transition-colors hover:decoration-soft">releases page</a>
    has the rest.
</p>
