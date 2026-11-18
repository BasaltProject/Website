<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('A Minecraft server rewritten from scratch')] class extends Component
{
    public function products(): array
    {
        return [
            [
                'name' => 'Basalt Server',
                'tagline' => 'The Minecraft server itself.',
                'body' => 'Vanilla parity is the goal for every system: same seed, same world,
                    same drops, same redstone, same mobs, bugs included. Then a plugin
                    platform on top of it.',
                'points' => [
                    'Lua plugins in .bspkg packages, sandboxed',
                    'Bukkit plugins through Basalt Shim, no Java on the host',
                    'bsw regions by default; Anvil and Linear worlds are converted',
                ],
                'route' => 'projects.server',
                'repo' => 'basalt.repos.server',
            ],
            [
                'name' => 'Basalt Proxy',
                'tagline' => 'One address in front of many servers.',
                'body' => 'A native proxy sharing Wire with the server: backends, forced hosts,
                    fallback, forwarding and Java and Bedrock clients.',
                'points' => [
                    'Lua plugins in .bppkg packages',
                    'Velocity, BungeeCord, BungeeGuard and Basalt forwarding',
                    'Velocity and BungeeCord plugin adapters, early',
                ],
                'route' => 'projects.proxy',
                'repo' => 'basalt.repos.proxy',
            ],
        ];
    }
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto grid max-w-6xl items-center gap-12 pt-10 pb-20 md:pt-16 lg:grid-cols-2 lg:gap-14 lg:pb-24">
            <div class="min-w-0">
                <span class="pill">C++, no external JVM</span>

                <h1 class="text-crystal mt-8 text-[clamp(2.4rem,5.6vw,4.2rem)] leading-[1.04] font-semibold tracking-[-.035em]">
                    A Minecraft server<br>rewritten <span class="text-crimson">from scratch.</span>
                </h1>

                <p class="mt-7 max-w-xl text-[clamp(1rem,2.1vw,1.18rem)] leading-relaxed text-soft">
                    Written entirely in C++ and shipped as one native binary per
                    platform. No bundled JRE and no hidden Java process. It has no garbage
                    collector to pause, and Java and Bedrock players share the same world.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <a href="{{ route('projects') }}" wire:navigate class="btn btn-primary">
                        Download Basalt
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
                        </svg>
                    </a>
                    <a href="{{ route('docs') }}" wire:navigate class="btn btn-ghost">Read the docs</a>
                </div>

                <div class="max-w-xl [&>div]:mt-8">
                    <x-early-development product="Basalt" />
                </div>
            </div>

            <div class="min-w-0">
                <x-terminal title="Basalt Server">
# verify the download (SHA256SUMS comes with each release)
sha256sum --check --ignore-missing SHA256SUMS

# make it executable and start it
chmod +x BasaltServer-*
./BasaltServer-&lt;version&gt;-Linux-x64
                </x-terminal>
            </div>
        </div>
    </section>

    <section class="px-4">
        <div class="mx-auto max-w-6xl">
            <dl class="grid w-full grid-cols-2 gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-4">
                @foreach ([
                    ['Minecraft', config('basalt.minecraft.current'), 'Native, older versions supported', 'text-crimson'],
                    ['Cross-play', 'Built in', 'Java and Bedrock', 'text-warped'],
                    ['Platforms', count(config('basalt.platforms')), 'macOS, Linux, Windows, Android, FreeBSD', 'text-ink'],
                    ['License', 'GPL-3.0', 'Open source', 'text-ink'],
                ] as [$label, $value, $note, $tone])
                    <div class="bg-surface px-5 py-6">
                        <dt class="text-[.68rem] font-medium tracking-[.18em] text-muted uppercase">{{ $label }}</dt>
                        <dd class="mt-2 text-2xl font-semibold tracking-tight {{ $tone }}">{{ $value }}</dd>
                        <dd class="mt-1 text-xs text-muted">{{ $note }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
                <span class="pill">Two projects</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.9rem,4vw,2.9rem)] leading-tight font-semibold tracking-[-.025em]">
                    The <span class="text-crimson">server</span> and the <span class="text-warped">proxy.</span>
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-soft">
                    They share their entire network layer through
                    <a href="{{ config('basalt.repos.wire') }}" target="_blank" rel="noopener noreferrer" class="text-ink underline decoration-line-strong underline-offset-4 transition-colors hover:decoration-soft">Wire</a>:
                    one implementation of the protocol, used by both sides.
                </p>
            </div>

            <div class="mt-14 grid gap-5 lg:grid-cols-2">
                @foreach ($this->products() as $index => $product)
                    @php
                        $proxy = $index === 1;
                    @endphp
                    <article class="glass card flex flex-col overflow-hidden p-8 md:p-10">
                        <span class="absolute inset-x-0 top-0 h-0.5 {{ $proxy ? 'bg-warped' : 'bg-crimson' }}" aria-hidden="true"></span>
                        <h3 class="text-2xl font-semibold tracking-tight text-ink">{{ $product['name'] }}</h3>
                        <p class="mt-1.5 text-sm text-muted">{{ $product['tagline'] }}</p>
                        <p class="mt-5 leading-relaxed text-soft">{{ $product['body'] }}</p>

                        <ul class="mt-7 space-y-3 text-sm text-soft">
                            @foreach ($product['points'] as $point)
                                <li class="flex gap-3">
                                    <x-icon.check class="mt-0.5 shrink-0 {{ $proxy ? 'text-warped' : 'text-crimson' }}" />
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto flex flex-wrap gap-3 pt-9">
                            <a href="{{ route($product['route']) }}" wire:navigate class="btn {{ $proxy ? 'btn-warped' : 'btn-primary' }} py-2.5 text-sm">Get {{ $product['name'] }}</a>
                            <a href="{{ config($product['repo']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost py-2.5 text-sm">Source</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
                <span class="pill">What you get</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.9rem,4vw,2.9rem)] leading-tight font-semibold tracking-[-.025em]">
                    Native first, and <span class="text-crimson">nothing left behind.</span>
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-soft">
                    The plugin API is Lua and it is the fast path. Everything else is here so
                    that moving to Basalt does not cost you what your server already runs on.
                </p>
            </div>

            <div class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'title' => 'Plugins in Lua, natively',
                            'icon' => '<path d="M8 6 3 12l5 6M16 6l5 6-5 6M14 4l-4 16"/>',
                            'body' => 'The plugin API is Lua, packaged and versioned, running in a sandbox with an instruction budget and a memory ceiling. No process boundary, no serialisation, no second heap.',
                            'info' => 'This is the fast path and the official API. Native plugins talk to the server directly, so anything performance sensitive belongs here.',
                        ],
                        [
                            'title' => 'Java and Bedrock together',
                            'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
                            'body' => 'Both editions join the same world. Bedrock (1.26.60) is built in and older Java clients are translated at the edge, with no proxy and no plugins to add. Bedrock support is newer and has known limits, listed in the documentation.',
                            'info' => null,
                        ],
                        [
                            'title' => 'Vanilla parity, bugs included',
                            'icon' => '<path d="m5 12 4.5 4.5L19 7"/><path d="M5 20h14"/>',
                            'body' => 'The goal is the same seed, the same world, the same drops, damage, redstone and mobs. Where vanilla does something strange, Basalt aims to do the same strange thing. Parity is not complete yet.',
                            'info' => null,
                        ],
                        [
                            'title' => 'No garbage collector',
                            'icon' => '<path d="M4 12h3l2-6 4 12 2-6h5"/>',
                            'body' => 'There is no JVM heap to size and no collector that can pause the tick. Memory is managed directly, so a stutter has a cause you can profile.',
                            'info' => null,
                        ],
                        [
                            'title' => 'Bukkit plugins, without Java',
                            'icon' => '<path d="M12 3 3 8v8l9 5 9-5V8l-9-5Z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
                            'body' => 'Drop a Bukkit, Spigot or Paper jar into plugins/ and the server runs it on Basalt Shim, a Java machine embedded in the binary. Velocity and BungeeCord adapters for the proxy are in early development.',
                            'info' => 'Shim executes Java bytecode and is built into the server. It starts only when a jar is present, and compatibility is still growing, so check the documentation for what is known to work.',
                        ],
                        [
                            'title' => 'One binary to deploy',
                            'icon' => '<path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4"/>',
                            'body' => 'A binary and a config folder. Nothing to install on the host and no runtime version to keep matched.',
                            'info' => null,
                        ],
                    ];
                @endphp

                @foreach ($features as $index => $feature)
                    <article class="glass card p-7">
                        <span @class([
                            'mb-5 inline-flex size-10 items-center justify-center rounded-lg',
                            'bg-[#3a2229] text-crimson' => $index % 2 === 0,
                            'bg-[#1c3739] text-warped' => $index % 2 === 1,
                        ]) aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $feature['icon'] !!}</svg>
                        </span>
                        {{-- The marker is inline so it follows the last word when the title wraps. --}}
                        <h3 class="text-lg font-semibold tracking-tight text-ink">{{ $feature['title'] }}@if ($feature['info'])<x-info class="ml-2" label="{{ $feature['title'] }}: details">{{ $feature['info'] }}</x-info>@endif</h3>
                        <p class="mt-3 text-sm leading-relaxed text-soft">{{ $feature['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="min-w-0">
                    <span class="pill">Plugins</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.7rem,3.4vw,2.5rem)] leading-tight font-semibold tracking-[-.025em]">
                        Written in <span class="text-warped">Lua,</span> sandboxed by default.
                    </h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Plugins are packaged with a manifest, code, resources and metadata,
                        and run inside a sandbox with an instruction budget. A
                        broken plugin is stopped without taking the server down.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        Text is authored in MiniMessage and rendered through the component
                        model, never through legacy colour codes.
                    </p>
                    <a href="{{ route('docs') }}" wire:navigate class="btn btn-ghost mt-8 py-2.5 text-sm">Plugin documentation</a>
                </div>

                <div class="min-w-0">
                    <x-code lang="lua" file="hello.lua">

on("player.join", function(e)
    e.player:send("<gradient:aqua:green>Welcome!</gradient>")
end)
                    </x-code>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-2xl border border-line bg-surface px-6 py-14 text-center md:px-12">
            <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-crimson to-warped" aria-hidden="true"></span>
            <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">
                Open source, <span class="text-crimson">all of it.</span>
            </h2>
            <p class="mx-auto mt-5 max-w-2xl leading-relaxed text-soft">
                The server, the proxy, the network layer they share and this site are all
                public. Read the code, file an issue, send a patch, or fork it and take it
                somewhere else.
            </p>
            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <a href="{{ route('projects') }}" wire:navigate class="btn btn-primary">Download Basalt</a>
                <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                    <x-icon.github />
                    Browse the source
                </a>
            </div>
        </div>
    </section>
</div>
