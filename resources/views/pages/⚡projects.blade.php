<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component
{
    public function projects(): array
    {
        return [
            [
                'name' => 'Basalt Server',
                'tagline' => 'The Minecraft server itself.',
                'body' => 'A complete reimplementation of the Minecraft server. Vanilla
                    parity, a native Lua plugin API, and the Bukkit ecosystem alongside it
                    when you need it.',
                'route' => 'projects.server',
                'repo' => config('basalt.repos.server'),
            ],
            [
                'name' => 'Basalt Proxy',
                'tagline' => 'One address in front of many servers.',
                'body' => 'A native proxy project sharing Wire with the server. Current development
                    builds validate configuration and serve local server-list status.',
                'route' => 'projects.proxy',
                'repo' => config('basalt.repos.proxy'),
            ],
        ];
    }
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-6xl pt-10 pb-16">
            <div class="reveal max-w-3xl">
                <span class="pill">Projects</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    Two binaries, five platforms.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/55">
                    Everything Basalt ships is a single native binary with no runtime to
                    install beside it, built for macOS, Linux and Windows. Pick the one you
                    need, or run both: the downloads are on each project's page.
                </p>
            </div>

            <div class="mt-14 grid gap-5 lg:grid-cols-2">
                @foreach ($this->projects() as $index => $project)
                    <article class="glass card flex flex-col p-8 reveal md:p-10" data-reveal-delay="{{ $index * 110 }}">
                        <h2 class="text-2xl font-semibold tracking-tight text-white/95">{{ $project['name'] }}</h2>
                        <p class="mt-1.5 text-sm text-white/40">{{ $project['tagline'] }}</p>
                        <p class="mt-6 leading-relaxed text-white/55">{{ $project['body'] }}</p>

                        <div class="mt-auto flex flex-wrap gap-3 pt-9">
                            <a href="{{ route($project['route']) }}" wire:navigate class="btn btn-primary py-2.5 text-sm">Downloads</a>
                            <a href="{{ $project['repo'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost py-2.5 text-sm">
                                <x-icon.github />
                                Source
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-4xl">
            <div class="glass reveal rounded-3xl p-8 text-center md:p-14">
                <h2 class="text-crystal text-[clamp(1.5rem,3vw,2rem)] font-semibold tracking-[-.025em]">Everything is on GitHub</h2>
                <p class="mx-auto mt-5 max-w-xl leading-relaxed text-white/50">
                    The server, the proxy, the network layer they share and the documentation
                    are separate repositories under one organisation, all GPL-3.0 except this
                    site.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    @foreach (['server' => 'Server', 'proxy' => 'Proxy', 'wire' => 'Wire', 'documentation' => 'Documentation'] as $key => $label)
                        <a href="{{ config("basalt.repos.$key") }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost py-2.5 text-sm">
                            <x-icon.github />
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
