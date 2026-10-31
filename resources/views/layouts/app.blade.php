@props([
    'title' => null,
    'description' => 'Basalt is a Minecraft: Java Edition server and proxy rewritten from scratch in C++23, with no JVM at runtime.',
])

@php
    $nav = [
        ['route' => 'home', 'label' => 'Home', 'pattern' => 'home'],
        ['route' => 'projects', 'label' => 'Projects', 'pattern' => 'projects*'],
        ['route' => 'docs', 'label' => 'Docs', 'pattern' => 'docs*'],
        ['route' => 'plugins', 'label' => 'Plugins', 'pattern' => 'plugins'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08080a">
    <meta name="description" content="{{ $description }}">
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon-64.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <title>{{ $title ? $title.' - Basalt' : 'Basalt' }}</title>
    @vite(['resources/css/dev.css', 'resources/js/dev.js'])
    @livewireStyles
</head>
<body class="min-h-dvh antialiased">
    {{-- Arms the scroll reveal. Inline and first because wire:navigate copies the
         incoming <html> attributes over the current ones, dropping any class added
         at runtime, and body scripts are re-run on every swap. --}}
    <script>
        document.documentElement.classList.add('js');

        // Fades the page in. Self-contained because this is the only script that
        // reliably re-runs on a wire:navigate swap.
        (function () {
            var main = document.currentScript.parentElement.querySelector('main');
            if (!main) return;

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            var duration = 420;
            var start = null;

            // Opacity only: animating a transform too would composite the whole
            // page subtree every frame.
            main.style.opacity = '0';
            main.style.willChange = 'opacity';

            function clear() {
                main.style.opacity = '';
                main.style.willChange = '';
            }

            function step(now) {
                if (start === null) start = now;

                var p = Math.min((now - start) / duration, 1);
                // Ease in and out: an ease-out curve spends half the opacity in
                // the first frames, which reads as a flicker.
                var eased = p < 0.5
                    ? 4 * p * p * p
                    : 1 - Math.pow(-2 * p + 2, 3) / 2;

                main.style.opacity = String(eased);

                if (p < 1) requestAnimationFrame(step);
                else clear();
            }

            requestAnimationFrame(step);
            // Never leave the page invisible if frames stop arriving.
            setTimeout(function () { if (main.style.opacity !== '') clear(); }, 1500);
        })();
    </script>

    {{-- wire:navigate replaces the whole body, so the background is persisted. --}}
    @persist('background')
        <div class="mesh" aria-hidden="true">
            <span class="blob blob-1"></span>
            <span class="blob blob-2"></span>
            <span class="blob blob-3"></span>
            <span class="blob blob-4"></span>
            <span class="blob blob-5"></span>
        </div>
        <div class="grain" aria-hidden="true"></div>
    @endpersist

    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-black">Skip to content</a>

    <header class="fixed inset-x-0 top-0 z-40 px-4 pt-4" data-nav>
        <nav class="glass glass-nav mx-auto flex max-w-6xl items-center gap-3 rounded-full py-2.5 pr-2.5 pl-6">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0" aria-label="Basalt home">
                <img src="/basalt-logo.svg" alt="Basalt" width="1220" height="450" class="h-7 w-auto">
            </a>

            <ul class="ml-3 hidden items-center gap-1 md:flex">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" wire:navigate
                           @class([
                               'rounded-full px-3.5 py-1.5 text-sm transition-colors duration-300',
                               'bg-white/10 text-white' => request()->routeIs($item['pattern']),
                               'text-white/55 hover:text-white/90' => ! request()->routeIs($item['pattern']),
                           ])>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('donate') }}" wire:navigate
                   @class([
                       'btn btn-ghost px-4 py-2 text-sm',
                       'border-white/24 bg-white/10 text-white' => request()->routeIs('donate'),
                   ])>
                    <x-icon.heart />
                    <span class="hidden sm:inline">Donate</span>
                </a>
                <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn-ghost px-4 py-2 text-sm">
                    <x-icon.github />
                    <span class="hidden sm:inline">GitHub</span>
                </a>
            </div>
        </nav>

        <ul class="glass glass-nav mx-auto mt-2 flex max-w-6xl items-center justify-around rounded-full px-2 py-2 md:hidden">
            @foreach ($nav as $item)
                <li>
                    <a href="{{ route($item['route']) }}" wire:navigate
                       @class([
                           'rounded-full px-3 py-1.5 text-[.82rem] transition-colors duration-300',
                           'bg-white/10 text-white' => request()->routeIs($item['pattern']),
                           'text-white/55' => ! request()->routeIs($item['pattern']),
                       ])>{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </header>

    <main id="content" class="relative z-10 pt-32 md:pt-28">
        {{ $slot }}
    </main>

    <footer class="relative z-10 mt-32 px-4 pb-12">
        <div class="mx-auto max-w-6xl">
            <div class="rule mb-10"></div>
            <div class="flex flex-col gap-10 md:flex-row md:justify-between">
                <div class="max-w-sm">
                    <img src="/basalt-logo.svg" alt="Basalt" width="1220" height="450" class="mb-5 w-36">
                    <p class="text-sm leading-relaxed text-white/45">
                        A Minecraft: Java Edition server and proxy rewritten from scratch in
                        C++23. No JVM at runtime.
                    </p>
                    <a href="{{ route('donate') }}" wire:navigate class="mt-5 inline-flex items-center gap-2 text-sm text-white/50 transition-colors hover:text-white/85">
                        <x-icon.heart />
                        Support the project
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-10 sm:grid-cols-3">
                    <div>
                        <h3 class="mb-3.5 text-[.72rem] font-semibold tracking-[.16em] text-white/85 uppercase">Projects</h3>
                        <ul class="space-y-2.5 text-sm text-white/45">
                            <li><a href="{{ route('projects.server') }}" wire:navigate class="transition-colors hover:text-white/85">Basalt Server</a></li>
                            <li><a href="{{ route('projects.proxy') }}" wire:navigate class="transition-colors hover:text-white/85">Basalt Proxy</a></li>
                            <li><a href="{{ route('plugins') }}" wire:navigate class="transition-colors hover:text-white/85">Plugins</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-3.5 text-[.72rem] font-semibold tracking-[.16em] text-white/85 uppercase">Learn</h3>
                        <ul class="space-y-2.5 text-sm text-white/45">
                            <li><a href="{{ route('docs') }}" wire:navigate class="transition-colors hover:text-white/85">Documentation</a></li>
                            <li><a href="{{ route('docs.show', ['section' => 'server', 'slug' => 'introduction']) }}" wire:navigate class="transition-colors hover:text-white/85">Server docs</a></li>
                            <li><a href="{{ route('docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="transition-colors hover:text-white/85">Proxy docs</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-3.5 text-[.72rem] font-semibold tracking-[.16em] text-white/85 uppercase">Source</h3>
                        <ul class="space-y-2.5 text-sm text-white/45">
                            <li><a href="{{ config('basalt.repos.server') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-white/85">Server</a></li>
                            <li><a href="{{ config('basalt.repos.proxy') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-white/85">Proxy</a></li>
                            <li><a href="{{ config('basalt.repos.wire') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-white/85">Wire</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col gap-2 text-xs text-white/30 sm:flex-row sm:items-center sm:justify-between">
                <p>Server and proxy under GPL-3.0. This site under MIT.</p>
                <p>Not affiliated with Mojang Studios or Microsoft. Minecraft is a trademark of Mojang Studios.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
