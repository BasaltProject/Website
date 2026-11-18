@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
])

@php
    $origin = rtrim(config('basalt.url'), '/');
    $canonical = $origin.'/'.ltrim(request()->path(), '/');
    $description ??= config('basalt.seo.'.request()->route()?->getName())
        ?? 'Basalt is a Minecraft server and proxy for Java and Bedrock, rewritten from scratch in C++, with no JVM at runtime.';
    $fullTitle = $title ? $title.' - Basalt' : 'Basalt';
    $ogPath = match (true) {
        request()->routeIs('projects.server') => '/og/server.png',
        request()->routeIs('projects.proxy') => '/og/proxy.png',
        request()->routeIs('docs.show') => '/og/docs/'.request()->route('section').'/'.request()->route('slug').'.png',
        default => '/og/home.png',
    };
    $noindex = $noindex || request()->routeIs('timings.*', 'plugins');
    $nav = [
        ['route' => 'home', 'label' => 'Home', 'pattern' => 'home'],
        ['route' => 'projects', 'label' => 'Projects', 'pattern' => 'projects*'],
        ['route' => 'docs', 'label' => 'Docs', 'pattern' => 'docs*'],
        ['route' => 'plugins', 'label' => 'Plugins', 'pattern' => 'plugins'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-pt-20">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111216">
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="me" href="https://mastodon.social/@basaltmc">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endif
    <meta property="og:site_name" content="Basalt">
    <meta property="og:type" content="{{ request()->routeIs('docs.show') ? 'article' : 'website' }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $origin }}{{ $ogPath }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_US">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $origin }}{{ $ogPath }}">
    @php
        $graph = [];

        if (request()->routeIs('home')) {
            $graph = [
                [
                    '@type' => 'Organization',
                    '@id' => $origin.'/#organization',
                    'name' => 'Basalt',
                    'url' => $origin.'/',
                    'logo' => $origin.'/apple-touch-icon.png',
                    'sameAs' => [config('basalt.github')],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $origin.'/#website',
                    'name' => 'Basalt',
                    'url' => $origin.'/',
                    'description' => $description,
                    'publisher' => ['@id' => $origin.'/#organization'],
                ],
            ];
        } elseif (request()->routeIs('projects.server', 'projects.proxy')) {
            $graph = [[
                '@type' => 'SoftwareApplication',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
                'applicationCategory' => 'DeveloperApplication',
                'operatingSystem' => 'macOS, Linux, Windows',
                'license' => 'https://www.gnu.org/licenses/gpl-3.0.html',
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
                'publisher' => ['@type' => 'Organization', 'name' => 'Basalt', 'url' => $origin.'/'],
            ]];
        } elseif (request()->routeIs('docs.show')) {
            $docPage = \App\Support\Docs::find(request()->route('section'), request()->route('slug'));
            $graph = [
                [
                    '@type' => 'TechArticle',
                    'headline' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'image' => $origin.$ogPath,
                    'dateModified' => $docPage ? date('c', $docPage['modified']) : null,
                    'publisher' => ['@type' => 'Organization', 'name' => 'Basalt', 'url' => $origin.'/'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Docs', 'item' => $origin.'/docs'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => \App\Support\Docs::SECTIONS[request()->route('section')]['title'] ?? 'Basalt'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $canonical],
                    ],
                ],
            ];
        }
    @endphp
    @if ($graph !== [])
        <script type="application/ld+json">{!! json_encode(['@'.'context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon-64.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <title>{{ $fullTitle }}</title>
    @fonts
    @vite(['resources/css/dev.css', 'resources/js/dev.js'])
    @livewireStyles
</head>
<body class="min-h-dvh antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-crimson focus:px-4 focus:py-2 focus:text-[#14100d]">Skip to content</a>

    <header class="fixed inset-x-0 top-0 z-40 border-b border-line bg-base" style="view-transition-name: site-nav"
            x-data="{ open: false }"
            x-on:keydown.escape.window="open = false"
            x-on:livewire:navigating.window="open = false">
        <nav class="relative z-10 bg-base mx-auto flex h-[72px] max-w-6xl items-center gap-3 px-4" aria-label="Main">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0" aria-label="Basalt home">
                <img src="/basalt-logo.svg" alt="Basalt" width="1220" height="450" class="h-11 w-auto">
            </a>

            <ul class="ml-6 hidden items-center gap-7 md:flex">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" wire:navigate
                           @class([
                               'border-b-2 py-1 text-[.95rem] transition-colors duration-150',
                               'border-crimson text-ink' => request()->routeIs($item['pattern']),
                               'border-base text-soft hover:text-ink' => ! request()->routeIs($item['pattern']),
                           ])>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="ml-auto flex items-center gap-1.5">
                <a href="{{ route('projects') }}" wire:navigate class="btn btn-primary px-3.5 py-2 text-sm">
                    <svg viewBox="0 0 16 16" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v8M4.5 7 8 10.5 11.5 7M3 13.5h10"/></svg>
                    <span>Download</span>
                </a>
                <a href="{{ route('donate') }}" wire:navigate aria-label="Donate"
                   @class([
                       'hidden size-9 items-center justify-center rounded-lg transition-colors duration-150 md:inline-flex',
                       'text-ink' => request()->routeIs('donate'),
                       'text-soft hover:text-ink' => ! request()->routeIs('donate'),
                   ])>
                    <x-icon.heart />
                </a>
                <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" aria-label="GitHub"
                   class="hidden size-9 items-center justify-center rounded-lg text-soft transition-colors duration-150 hover:text-ink md:inline-flex">
                    <x-icon.github />
                </a>
                <button type="button" class="inline-flex size-9 items-center justify-center rounded-lg text-soft transition-colors duration-150 hover:text-ink md:hidden"
                        aria-label="Toggle menu" aria-controls="mobile-menu" x-bind:aria-expanded="open.toString()" aria-expanded="false"
                        x-on:click="open = ! open">
                    <svg x-show="! open" viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M2 4h12M2 8h12M2 12h12"/></svg>
                    <svg x-show="open" x-cloak viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M3.5 3.5l9 9M12.5 3.5l-9 9"/></svg>
                </button>
            </div>
        </nav>

        <div id="mobile-menu" x-show="open" x-cloak
             x-transition:enter="transition-transform duration-200 ease-out motion-reduce:transition-none"
             x-transition:enter-start="-translate-y-[calc(100%+80px)]"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform duration-150 ease-in motion-reduce:transition-none"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="-translate-y-[calc(100%+80px)]"
             class="relative border-t border-line bg-base md:hidden">
            <ul class="mx-auto max-w-6xl px-4 py-3">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" wire:navigate
                           @class([
                               'block rounded-lg px-3 py-3 text-base',
                               'text-ink' => request()->routeIs($item['pattern']),
                               'text-soft' => ! request()->routeIs($item['pattern']),
                           ])><span @class([
                                   'inline-block border-b-2 pb-1 transition-colors duration-150',
                                   'border-crimson' => request()->routeIs($item['pattern']),
                                   'border-transparent' => ! request()->routeIs($item['pattern']),
                               ])>{{ $item['label'] }}</span></a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('donate') }}" wire:navigate
                       @class([
                           'block rounded-lg px-3 py-3 text-base',
                           'text-ink' => request()->routeIs('donate'),
                           'text-soft' => ! request()->routeIs('donate'),
                       ])><span @class([
                                   'inline-block border-b-2 pb-1 transition-colors duration-150',
                                   'border-crimson' => request()->routeIs('donate'),
                                   'border-transparent' => ! request()->routeIs('donate'),
                               ])>Donate</span></a>
                </li>
                <li>
                    <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg px-3 py-3 text-base text-soft"><span class="inline-block border-b-2 border-transparent pb-1">GitHub</span></a>
                </li>
            </ul>
            <div class="absolute inset-x-0 top-full"><x-basalt-edge gradient="basalt-edge-menu" /></div>
        </div>
    </header>

    <main id="content" wire:transition.navigate class="relative z-10 pt-[72px]">
        {{ $slot }}
    </main>

    <footer style="view-transition-name: site-footer" class="relative z-10 mt-32 bg-surface px-4 pt-[calc(max(5vw,80px)+2.5rem)] pb-10">
        <x-basalt-edge />
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,2.7fr)]">
                <div class="max-w-sm">
                    <img src="/basalt-logo.svg" alt="Basalt" width="1220" height="450" class="h-12 w-auto">
                    <p class="mt-5 text-sm leading-relaxed text-soft">
                        A Minecraft server and proxy for Java and Bedrock, rewritten from scratch in
                        C++. No JVM at runtime.
                    </p>
                    <span class="mt-4 inline-block rounded-full border border-line bg-raised px-2.5 py-1 text-xs text-muted">Minecraft {{ config('basalt.minecraft.current') }}</span>
                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <a href="{{ route('projects') }}" wire:navigate class="btn btn-primary px-3.5 py-2 text-sm">Download</a>
                        <a href="{{ route('donate') }}" wire:navigate class="btn btn-ghost px-3.5 py-2 text-sm">Donate</a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-10 sm:grid-cols-4">
                <div>
                    <h3 class="mb-3.5 text-[.9rem] font-semibold text-ink">Projects</h3>
                    <ul class="space-y-2.5 text-[.9rem] text-soft">
                        <li><a href="{{ route('projects.server') }}" wire:navigate class="transition-colors hover:text-crimson">Basalt Server</a></li>
                        <li><a href="{{ route('projects.proxy') }}" wire:navigate class="transition-colors hover:text-crimson">Basalt Proxy</a></li>
                        <li><a href="{{ route('plugins') }}" wire:navigate class="transition-colors hover:text-crimson">Plugins</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-3.5 text-[.9rem] font-semibold text-ink">Learn</h3>
                    <ul class="space-y-2.5 text-[.9rem] text-soft">
                        <li><a href="{{ route('docs') }}" wire:navigate class="transition-colors hover:text-crimson">Documentation</a></li>
                        <li><a href="{{ route('docs.show', ['section' => 'server', 'slug' => 'introduction']) }}" wire:navigate class="transition-colors hover:text-crimson">Server docs</a></li>
                        <li><a href="{{ route('docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="transition-colors hover:text-crimson">Proxy docs</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-3.5 text-[.9rem] font-semibold text-ink">Source</h3>
                    <ul class="space-y-2.5 text-[.9rem] text-soft">
                        <li><a href="{{ config('basalt.repos.server') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-crimson">Server</a></li>
                        <li><a href="{{ config('basalt.repos.proxy') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-crimson">Proxy</a></li>
                        <li><a href="{{ config('basalt.repos.wire') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-crimson">Wire</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-3.5 text-[.9rem] font-semibold text-ink">Support</h3>
                    <ul class="space-y-2.5 text-[.9rem] text-soft">
                        <li><a href="{{ route('donate') }}" wire:navigate class="transition-colors hover:text-crimson">Donate</a></li>
                        <li><a href="{{ config('basalt.donate.url') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-crimson">GitHub Sponsors</a></li>
                        <li><a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-crimson">GitHub</a></li>
                    </ul>
                </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col gap-2 border-t border-line pt-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
                <p>Server and proxy under GPL-3.0. This site under MIT.</p>
                <p>Not affiliated with Mojang Studios or Microsoft. Minecraft is a trademark of Mojang Studios.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
