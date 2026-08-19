@props(['repo', 'artifact'])

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach (config('basalt.platforms') as $index => $platform)
        <a href="{{ $repo }}/releases/latest/download/{{ $artifact }}-{{ $platform['target'] }}.tar.gz"
           class="glass card flex items-center gap-4 p-5 reveal" data-reveal-delay="{{ $index * 70 }}">
            <x-icon.brand :name="$platform['icon']" class="h-6 w-auto shrink-0 text-white/55" />
            <div class="min-w-0">
                <p class="font-medium text-white/85">{{ $platform['label'] }}</p>
                <p class="truncate text-sm text-white/40">{{ $platform['arch'] }}</p>
            </div>
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 text-white/30" aria-hidden="true">
                <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
            </svg>
        </a>
    @endforeach
</div>

<p class="mt-5 text-sm text-white/40">
    Every release also carries checksums and the full changelog on
    <a href="{{ $repo }}/releases" target="_blank" rel="noopener noreferrer" class="text-white/70 underline decoration-white/25 underline-offset-4 transition-colors hover:decoration-white/70">the releases page</a>.
</p>
