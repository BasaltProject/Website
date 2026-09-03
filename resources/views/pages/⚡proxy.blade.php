<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Basalt Proxy')] class extends Component
{
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-6xl pt-10 pb-16">
            <a href="{{ route('dev.projects') }}" wire:navigate class="mb-8 inline-flex items-center gap-2 text-sm text-white/40 transition-colors hover:text-white/75">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>
                All projects
            </a>

            <div class="reveal max-w-3xl">
                <span class="pill">Basalt Proxy</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    One address, every server.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/55">
                    Basalt Proxy sits in front of a set of Minecraft servers and gives players
                    one address to connect to, moving them between backends without a
                    reconnect. Written entirely in C++23, shipped as a native binary
                    per platform.
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#downloads" class="btn btn-primary">
                        Download
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                    </a>
                    <a href="{{ route('dev.docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Documentation</a>
                    <a href="{{ config('basalt.repos.proxy') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                        <x-icon.github />
                        Repository
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="downloads" class="scroll-mt-32 px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="reveal">
                <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Download</h2>
                <p class="mt-4 max-w-2xl leading-relaxed text-white/50">
                    One binary and a config folder. On first start it writes its
                    configuration, one TOML file per domain, with comments and defaults.
                </p>
            </div>
            <div class="mt-9">
                <x-download-grid :repo="config('basalt.repos.proxy')" artifact="basalt-proxy" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                <div class="reveal">
                    <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Not only for Basalt Server</h2>
                    <p class="mt-5 leading-relaxed text-white/55">
                        A proxy that only works with its own server is a proxy nobody can
                        adopt. Basalt Proxy speaks the standard protocol to whatever is behind
                        it, so vanilla, Paper, Spigot, Fabric and anything else that accepts a
                        Minecraft connection all work.
                    </p>
                    <p class="mt-4 leading-relaxed text-white/55">
                        Basalt Server behind it is the better case, not the required one: the
                        two can hand work to each other rather than each doing it alone.
                    </p>

                    <h3 class="mt-10 text-xl font-semibold tracking-tight text-white/90">Every client, one address</h3>
                    <p class="mt-4 leading-relaxed text-white/55">
                        The proxy speaks the newest protocol natively, and everything else is
                        normalised before it goes any further. Your players connect with
                        whatever they already have.
                    </p>

                    <div class="glass mt-7 overflow-hidden rounded-2xl">
                        <ul class="divide-y divide-white/6 text-sm">
                            @foreach ([
                                ['Current Java Edition', 'Direct, no translation'],
                                ['Older Java Edition', 'Translated at the edge'],
                                ['Bedrock Edition', 'Translated at the edge'],
                            ] as [$client, $how])
                                <li class="flex items-center gap-4 px-6 py-3.5">
                                    <span class="text-white/70">{{ $client }}</span>
                                    <span class="ml-auto text-white/40">{{ $how }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-8">
                        <x-code lang="toml" file="config/servers.toml">
[[servers]]
name = "lobby"
address = "127.0.0.1:25566"

[[servers]]
name = "survival"
address = "127.0.0.1:25567"

[routing]
default = "lobby"
                        </x-code>
                    </div>
                </div>

                <dl class="glass reveal divide-y divide-white/6 self-start overflow-hidden rounded-2xl text-sm">
                    @foreach ([
                        ['Language', 'C++23, 100 %'],
                        ['Minecraft', config('basalt.minecraft.current').' native, older versions supported'],
                        ['Clients', 'Java Edition and Bedrock Edition'],
                        ['Backends', 'Any Minecraft server, Basalt or not'],
                        ['Distribution', 'Native binaries per platform'],
                        ['Plugin API', 'Lua, .bppkg packages'],
                        ['JVM plugins', 'Velocity and BungeeCord, optional'],
                        ['License', 'GPL-3.0'],
                    ] as [$label, $value])
                        <div class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:items-baseline sm:gap-6">
                            <dt class="shrink-0 text-white/40 sm:w-32">{{ $label }}</dt>
                            <dd class="text-white/80">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="reveal max-w-2xl">
                <span class="pill">Translation</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Done wherever it is cheapest</h2>
                <p class="mt-5 leading-relaxed text-white/55">
                    Older Java clients and Bedrock clients are both supported by translating at
                    the edge, and both the proxy and the server carry that machinery. The proxy
                    is the process with the most connections open at once, so translating every
                    one of them there is the most expensive place to do it. The default is to
                    push that work down when it can be pushed.
                </p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2">
                @foreach ([
                    ['Backend is Basalt Server', 'The server translates', 'The proxy is the connection-heavy process. The two speak to each other well enough to hand the work over.'],
                    ['Backend is anything else', 'The proxy translates', 'A vanilla or Paper backend only understands its own version, so the proxy has to do it.'],
                ] as $index => [$situation, $who, $why])
                    <article class="glass card p-8 reveal" data-reveal-delay="{{ $index * 110 }}">
                        <p class="text-[.68rem] font-medium tracking-[.16em] text-white/35 uppercase">{{ $situation }}</p>
                        <h3 class="mt-3 text-xl font-semibold tracking-tight text-white/90">{{ $who }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-white/50">{{ $why }}</p>
                    </article>
                @endforeach
            </div>

            <p class="reveal mt-8 max-w-3xl text-sm leading-relaxed text-white/40">
                Both halves are configurable. An operator who wants every connection translated
                at the proxy, or never translated at all, says so in the configuration. That is
                the default, not a constraint.
            </p>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-5 lg:grid-cols-2">
                <article class="glass card p-8 reveal md:p-10">
                    <span class="pill">Basalt plugins</span>
                    <h2 class="mt-6 text-xl font-semibold tracking-tight text-white/90">Its own Lua API</h2>
                    <p class="mt-4 leading-relaxed text-white/55">
                        Plugins are written in Lua and packaged as
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">.bppkg</code>
                        files, the same shape as the server's
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">.bspkg</code>,
                        and run in a sandbox with a per-tick instruction budget.
                    </p>
                    <div class="mt-6">
                        <x-code lang="lua" file="greet.lua">

basalt.on("player_connect", function(event)
    event.player:send_to("lobby")
end)
                        </x-code>
                    </div>
                </article>

                <article class="glass card p-8 reveal md:p-10" data-reveal-delay="110">
                    <span class="pill">Velocity and BungeeCord</span>
                    <h2 class="mt-6 text-xl font-semibold tracking-tight text-white/90">Run the plugins you have<x-info class="ml-2" label="JVM plugin support: how it works">Optional. The proxy core, its network layer and its own plugins are native, and a proxy started without this feature never looks for Java. You supply the JVM.</x-info></h2>
                    <p class="mt-4 leading-relaxed text-white/55">
                        Most of the existing proxy ecosystem is Velocity and BungeeCord
                        plugins. Basalt Proxy runs them, and doing so is opt-in.
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-white/50">
                        @foreach ([
                            'The core, its network layer and its own plugins stay native.',
                            'Enabling it means supplying a JVM and accepting what that costs.',
                            'A proxy started without it never looks for Java.',
                        ] as $point)
                            <li class="flex gap-3">
                                <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </div>
    </section>
    <section class="px-4 pt-8 pb-20">
        <div class="mx-auto max-w-4xl">
            {{-- Same panel as the call to action on the projects page. --}}
            <div class="glass reveal rounded-3xl p-8 text-center md:p-14">
                <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">
                    Put it in front of your network.
                </h2>
                <p class="mx-auto mt-5 max-w-xl leading-relaxed text-white/50">
                    One binary and a config folder. It speaks the standard protocol, so whatever is behind it today keeps working.
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    {{-- Back up to the downloads rather than straight to a file: the arrow
                         points the way it actually goes. --}}
                    <a href="#downloads" class="btn btn-primary">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 13V4M4.5 7.5 8 4l3.5 3.5"/>
                        </svg>
                        Download Basalt Proxy
                    </a>
                    <a href="{{ route('dev.docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Read the docs</a>
                </div>
            </div>
        </div>
    </section>
</div>
