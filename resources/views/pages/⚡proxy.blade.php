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
            <a href="{{ route('projects') }}" wire:navigate class="mb-8 inline-flex items-center gap-2 text-sm text-muted transition-colors hover:text-warped">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>
                All projects
            </a>

            <div class="max-w-3xl">
                <span class="pill">Basalt Proxy</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    One address, <span class="text-warped">every server.</span>
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-soft">
                    A native Minecraft proxy written in C++23, sharing its
                    network library with Basalt Server.
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#downloads" class="btn btn-warped">
                        Download
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                    </a>
                    <a href="{{ route('docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Documentation</a>
                    <a href="{{ config('basalt.repos.proxy') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                        <x-icon.github />
                        Repository
                    </a>
                </div>

                <x-early-development product="Basalt Proxy" />
            </div>
        </div>
    </section>

    <section id="downloads" class="scroll-mt-32 px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div>
                <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Download</h2>
                <p class="mt-4 max-w-2xl leading-relaxed text-soft">
                    Native builds, configured with TOML. Each file is the executable itself.
                </p>
            </div>
            <div class="mt-9">
                <x-download-grid :repo="config('basalt.repos.proxy')" project="proxy" accent="warped" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">What works today</h2>
                <p class="mt-5 leading-relaxed text-soft">
                    The proxy listens on one address, authenticates players, and relays them to
                    your backends with the identity forwarding each one expects: Basalt's own
                    signed scheme, Velocity, BungeeCord, BungeeGuard or none. Routing, forced
                    hosts, fallback, moving players between backends and Lua plugins are
                    implemented.
                </p>
                <p class="mt-4 leading-relaxed text-soft">
                    It is young. Testing so far uses automated clients against vanilla, Paper and
                    Basalt backends, and graphical clients are not yet part of it. Older Java
                    clients and Bedrock are translated, with limits that the documentation lists.
                    Velocity and BungeeCord plugin adapters are planned on top of Basalt Shim and
                    do not exist yet.
                </p>
            </div>
        </div>
    </section>
</div>
