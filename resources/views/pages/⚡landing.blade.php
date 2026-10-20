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
                'body' => 'Vanilla parity is the acceptance criterion for every system:
                    same seed, same world, same drops, same redstone, same mobs, bugs
                    included. Then a plugin platform on top of it.',
                'points' => [
                    'Lua plugins in .bspkg packages, sandboxed',
                    'Bukkit adapter planned after Shim',
                    'Linear regions, Anvil worlds converted on load',
                ],
                'route' => 'dev.projects.server',
                'repo' => 'basalt.repos.server',
            ],
            [
                'name' => 'Basalt Proxy',
                'tagline' => 'One address in front of many servers.',
                'body' => 'A native proxy project sharing Wire with the server. Current development
                    builds provide command-line help and version information.',
                'points' => [
                    'Native Lua plugins planned in .bppkg packages',
                    'Velocity and BungeeCord adapters planned after Shim',
                    'Configurable translation and routing in development',
                ],
                'route' => 'dev.projects.proxy',
                'repo' => 'basalt.repos.proxy',
            ],
        ];
    }
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto flex max-w-4xl flex-col items-center pt-10 pb-24 text-center md:pt-16 md:pb-32">
            <span class="pill reveal">C++23, no external JVM</span>

            <h1 class="text-crystal reveal mt-8 text-[clamp(2.6rem,7vw,4.9rem)] leading-[1.04] font-semibold tracking-[-.035em]" data-reveal-delay="80">
                A Minecraft server<br>rewritten from scratch.
            </h1>

            <p class="reveal mt-7 max-w-2xl text-[clamp(1rem,2.1vw,1.18rem)] leading-relaxed text-white/55" data-reveal-delay="160">
                Written entirely in C++23 and shipped as one native binary per
                platform. No bundled JRE, no embedded VM, no hidden Java process. It starts
                in milliseconds, never pauses to collect garbage, and Java and Bedrock
                players share the same world.
            </p>

            <div class="reveal mt-10 flex flex-wrap items-center justify-center gap-3" data-reveal-delay="240">
                <a href="{{ route('dev.projects') }}" wire:navigate class="btn btn-primary">
                    Download Basalt
                    <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/>
                    </svg>
                </a>
                <a href="{{ route('dev.docs') }}" wire:navigate class="btn btn-ghost">Read the docs</a>
            </div>

            <dl class="reveal mt-20 grid w-full grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/8 bg-white/8 sm:grid-cols-4" data-reveal-delay="320">
                @foreach ([
                    ['Minecraft', config('basalt.minecraft.current'), 'Native, older versions supported'],
                    ['Cross-play', 'Built in', 'Java and Bedrock'],
                    ['Platforms', count(config('basalt.platforms')), 'macOS, Linux, Windows'],
                    ['License', 'GPL-3.0', 'Open source'],
                ] as [$label, $value, $note])
                    <div class="bg-[#0b0b0e]/70 px-5 py-6 backdrop-blur-xl">
                        <dt class="text-[.68rem] font-medium tracking-[.18em] text-white/40 uppercase">{{ $label }}</dt>
                        <dd class="mt-2 text-2xl font-semibold tracking-tight text-white/90">{{ $value }}</dd>
                        <dd class="mt-1 text-xs text-white/35">{{ $note }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="reveal max-w-2xl">
                <span class="pill">What you get</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.9rem,4vw,2.9rem)] leading-tight font-semibold tracking-[-.025em]">
                    Native first, and nothing left behind.
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-white/50">
                    The plugin API is Lua and it is the fast path. Everything else is here so
                    that moving to Basalt does not cost you what your server already runs on.
                </p>
            </div>

            <div class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'title' => 'Plugins in Lua, natively',
                            'body' => 'The plugin API is Lua, packaged and versioned, running in a sandbox with a per-tick instruction budget. No process boundary, no serialisation, no second heap.',
                            'info' => 'This is the fast path and the official API. Native plugins talk to the server directly, so anything performance sensitive belongs here.',
                        ],
                        [
                            'title' => 'Java and Bedrock together',
                            'body' => 'Both editions join the same world. Bedrock and older Java clients are translated at the edge, built in, with no proxy and no plugins to add.',
                            'info' => null,
                        ],
                        [
                            'title' => 'Vanilla parity, bugs included',
                            'body' => 'Same seed, same world. Same drops, same damage, same redstone, same mobs. Where vanilla does something strange, Basalt does the same strange thing.',
                            'info' => null,
                        ],
                        [
                            'title' => 'No GC pauses, ever',
                            'body' => 'There is no heap to size and no collector to hide behind. Tick 1 and tick 1,000,000 cost the same, and no player sees a stutter you cannot explain.',
                            'info' => null,
                        ],
                        [
                            'title' => 'Building on the plugin ecosystem',
                            'body' => 'Future Server modules will implement Bukkit compatibility, and Proxy modules will implement Velocity and BungeeCord APIs once Shim is complete.',
                            'info' => 'Shim provides Java bytecode execution through a JVM in C++. Server and Proxy provide their own plugin API adapters. This integration is planned, requires no external JVM installation and will be optional.',
                        ],
                        [
                            'title' => 'One binary to deploy',
                            'body' => 'A binary and a config folder. Nothing to install on the host, no runtime version to keep matched, and the same artefact on every machine.',
                            'info' => null,
                        ],
                    ];
                @endphp

                @foreach ($features as $index => $feature)
                    <article class="glass card p-7 reveal" data-reveal-delay="{{ ($index % 3) * 90 }}">
                        {{-- The marker is inline so it follows the last word when the title wraps. --}}
                        <h3 class="text-lg font-semibold tracking-tight text-white/90">{{ $feature['title'] }}@if ($feature['info'])<x-info class="ml-2" label="{{ $feature['title'] }}: details">{{ $feature['info'] }}</x-info>@endif</h3>
                        <p class="mt-3 text-sm leading-relaxed text-white/50">{{ $feature['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="reveal max-w-2xl">
                <span class="pill">Two projects</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.9rem,4vw,2.9rem)] leading-tight font-semibold tracking-[-.025em]">
                    The server and the proxy.
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-white/50">
                    They share their entire network layer through
                    <a href="{{ config('basalt.repos.wire') }}" target="_blank" rel="noopener noreferrer" class="text-white/75 underline decoration-white/25 underline-offset-4 transition-colors hover:decoration-white/70">Wire</a>:
                    one implementation of the protocol, used by both sides.
                </p>
            </div>

            <div class="mt-14 grid gap-5 lg:grid-cols-2">
                @foreach ($this->products() as $index => $product)
                    <article class="glass card flex flex-col p-8 reveal md:p-10" data-reveal-delay="{{ $index * 110 }}">
                        <h3 class="text-2xl font-semibold tracking-tight text-white/95">{{ $product['name'] }}</h3>
                        <p class="mt-1.5 text-sm text-white/40">{{ $product['tagline'] }}</p>
                        <p class="mt-5 leading-relaxed text-white/60">{{ $product['body'] }}</p>

                        <ul class="mt-7 space-y-3 text-sm text-white/50">
                            @foreach ($product['points'] as $point)
                                <li class="flex gap-3">
                                    <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto flex flex-wrap gap-3 pt-9">
                            <a href="{{ route($product['route']) }}" wire:navigate class="btn btn-primary py-2.5 text-sm">Get {{ $product['name'] }}</a>
                            <a href="{{ config($product['repo']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost py-2.5 text-sm">Source</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-6xl">
            <div class="reveal">
                <div class="grid gap-10 md:grid-cols-2">
                    <div>
                        <span class="pill">Plugins</span>
                        <h2 class="text-crystal mt-6 text-[clamp(1.7rem,3.4vw,2.5rem)] leading-tight font-semibold tracking-[-.025em]">
                            Written in Lua, sandboxed by default.
                        </h2>
                        <p class="mt-5 leading-relaxed text-white/50">
                            Plugins are packaged with a manifest, code, resources and metadata,
                            and run inside a sandbox with a per-tick instruction budget. A
                            broken plugin cannot take the server down.
                        </p>
                        <p class="mt-4 leading-relaxed text-white/50">
                            Text is authored in MiniMessage and rendered through the component
                            model, never through legacy colour codes.
                        </p>
                        <a href="{{ route('dev.docs') }}" wire:navigate class="btn btn-ghost mt-8 py-2.5 text-sm">Plugin documentation</a>
                    </div>

                    <div class="self-center">
                        <x-code lang="lua" file="hello.lua">

basalt.on("player_join", function(event)
    event.player:send(
        "<gradient:aqua:green>Welcome!</gradient>"
    )
end)
                        </x-code>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-20">
        <div class="mx-auto max-w-3xl text-center">
            <div class="reveal">
                <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">
                    Open source, all of it.
                </h2>
                <p class="mt-5 leading-relaxed text-white/50">
                    The server, the proxy, the network layer they share and this site are all
                    public. Read the code, file an issue, send a patch, or fork it and take it
                    somewhere else.
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('dev.projects') }}" wire:navigate class="btn btn-primary">Download Basalt</a>
                    <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                        <x-icon.github />
                        Browse the source
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
