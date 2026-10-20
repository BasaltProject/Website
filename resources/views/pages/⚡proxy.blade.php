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
                    A native Minecraft proxy project written in C++23, sharing its
                    network library with Basalt Server.
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
                    Native development builds with TOML configuration and local server-list status.
                </p>
            </div>
            <div class="mt-9">
                <x-download-grid :repo="config('basalt.repos.proxy')" artifact="basalt-proxy" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="reveal max-w-3xl">
                <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">In development</h2>
                <p class="mt-5 leading-relaxed text-white/55">
                    The current executable validates TOML configuration and serves local status
                    and ping requests through a native listener. Player authentication, routing
                    and plugins are being developed.
                </p>
                <p class="mt-4 leading-relaxed text-white/55">
                    The goal is one address for Minecraft backends, Java and Bedrock support,
                    and configurable translation shared with Basalt Server through Wire.
                </p>
                <p class="mt-4 leading-relaxed text-white/55">
                    Native Lua plugins will follow the server's API conventions. Future Velocity
                    and BungeeCord adapters will use Shim for Java bytecode execution; Proxy will
                    implement their APIs, without requiring an external JVM installation.
                </p>
            </div>
        </div>
    </section>
</div>
