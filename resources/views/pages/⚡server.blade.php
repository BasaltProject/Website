<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Basalt Server')] class extends Component
{
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-6xl pt-10 pb-16">
            <a href="{{ route('projects') }}" wire:navigate class="mb-8 inline-flex items-center gap-2 text-sm text-muted transition-colors hover:text-white/75">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>
                All projects
            </a>

            <div class="max-w-3xl">
                <span class="pill">Basalt Server</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    The Minecraft server, rebuilt.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-soft">
                    A complete reimplementation of the Minecraft server, written entirely in
                    C++23 and shipped as a native binary for each platform. It aims to play
                    exactly like vanilla, and it has no garbage collector to pause the tick.
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#downloads" class="btn btn-primary">
                        Download
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v9M4.5 7.5 8 11l3.5-3.5M3 13.5h10"/></svg>
                    </a>
                    <a href="{{ route('docs.show', ['section' => 'server', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Documentation</a>
                    <a href="{{ config('basalt.repos.server') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                        <x-icon.github />
                        Repository
                    </a>
                </div>

                <x-early-development product="Basalt Server" />
            </div>
        </div>
    </section>

    <section id="downloads" class="scroll-mt-32 px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div>
                <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Download</h2>
                <p class="mt-4 max-w-2xl leading-relaxed text-soft">
                    One binary, nothing else to install. Download it, make it executable
                    (chmod +x on Linux and macOS) and run it: the server asks you to accept the
                    EULA and writes its own configuration next to itself on first start.
                </p>
            </div>
            <div class="mt-9">
                <x-download-grid :repo="config('basalt.repos.server')" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                <div>
                    <h2 class="text-crystal text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Parity is the requirement</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Basalt Server is built to behave exactly like the official one with no
                        plugins: same seed, same world, same drops, same damage, same redstone,
                        same mobs, bugs included. Parity is the acceptance criterion for every
                        system, and it is not finished: some mechanics are still missing.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        Anything that makes the server behave differently from vanilla is
                        opt-in and labelled as such in the generated configuration comments.
                        The default path is always the vanilla one.
                    </p>
                    <h3 class="mt-10 text-xl font-semibold tracking-tight text-white/90">Every client, one server</h3>
                    <p class="mt-4 leading-relaxed text-soft">
                        Basalt tracks the newest Minecraft release rather than pinning a
                        version. The core speaks one protocol, the current one, and every
                        other client is normalised before it reaches it. Gameplay code never
                        learns that older clients exist.
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
                                    <span class="ml-auto text-muted">{{ $how }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <dl class="glass divide-y divide-white/6 self-start overflow-hidden rounded-2xl text-sm">
                    @foreach ([
                        ['Language', 'C++23'],
                        ['Minecraft', config('basalt.minecraft.current').' native, older versions supported'],
                        ['Clients', 'Java Edition and Bedrock Edition'],
                        ['Distribution', 'Native binaries per platform'],
                        ['World storage', 'Linear regions; Anvil regions read and converted'],
                        ['Plugin API', 'Lua, .bspkg packages'],
                        ['Bukkit plugins', 'Optional, through Basalt Shim'],
                        ['Configuration', 'config/, one TOML file per domain'],
                        ['License', 'GPL-3.0'],
                    ] as [$label, $value])
                        <div class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:items-baseline sm:gap-6">
                            <dt class="shrink-0 text-muted sm:w-36">{{ $label }}</dt>
                            <dd class="text-white/80">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div>
                <div class="grid gap-12 md:grid-cols-2">
                    <div>
                        <span class="pill">Plugins</span>
                        <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Lua, sandboxed, packaged</h2>
                        <p class="mt-5 leading-relaxed text-soft">
                            Plugins are written in Lua and packaged as
                            <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">.bspkg</code>
                            files, each with a manifest, code, resources and metadata. They run
                            inside a sandbox with a instruction budget and a memory ceiling, so a
                            broken plugin is stopped without taking the server down.
                        </p>
                        <p class="mt-4 leading-relaxed text-soft">
                            The Lua runtime is the official Lua 5.5.1, built from source into
                            the binary, so it is the same runtime everywhere Basalt runs with no
                            Lua to install alongside it and no per-platform build of your plugin.
                        </p>
                    </div>

                    <div class="self-center">
                        <x-code lang="lua" file="hello.lua">

basalt.on("player_join", function(event)
    event.player:send(
        "<gradient:aqua:green>Welcome!</gradient>"
    )
end)
                        </x-code>
                        <p class="mt-5 text-sm leading-relaxed text-muted">
                            Text is authored in MiniMessage and rendered through the component
                            model, never through legacy colour codes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                <div>
                    <span class="pill">Bukkit</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Keep your plugins<x-info class="ml-3" label="Bukkit support: how it works">Powered by Basalt Shim, a Java machine embedded in the server binary. Nothing Java starts until a jar is in plugins/, and no JDK is needed on the host.</x-info></h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Most of the ecosystem is Bukkit, Spigot and Paper plugins that will
                        never be rewritten. Basalt runs them through Basalt Shim, without an
                        external JVM. Compatibility is still growing: the documentation lists
                        what is known to work.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        There is no host Java path to configure and no JDK to install. With no
                        jar in plugins/, no Java machine starts at all.
                    </p>
                    <a href="{{ route('docs.show', ['section' => 'server', 'slug' => 'bukkit-plugins']) }}" wire:navigate class="btn btn-ghost mt-8 py-2.5 text-sm">How it works</a>
                </div>

                <div class="self-center">
                    <x-code file="plugins/">
plugins/
  my-lua-plugin/
  other.bspkg
  WorldEdit.jar
                    </x-code>
                    <div class="glass mt-4 overflow-hidden rounded-2xl">
                        <ul class="divide-y divide-white/6 text-sm">
                            @foreach ([
                                ['Maximum performance, no JVM at all', 'Native Lua plugins'],
                                ['A plugin that already exists', 'A Bukkit jar'],
                                ['Both at once', 'They run side by side'],
                            ] as [$want, $use])
                                <li class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:items-center sm:gap-6">
                                    <span class="text-soft">{{ $want }}</span>
                                    <span class="text-white/85 sm:ml-auto sm:text-right">{{ $use }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
                <div>
                    <span class="pill">Worlds</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Multi-world from the first line</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Not a plugin bolted onto a server that assumes one save. A world
                        here <em class="text-white/75 not-italic">is</em> a dimension, so
                        there is no folder with three dimensions buried inside it and no
                        machinery pretending otherwise.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">worlds.toml</code>
                        declares what to load, and only that. It never deletes anything.
                    </p>

                    <ul class="mt-7 space-y-3.5 text-sm text-soft">
                        @foreach ([
                            'Add a table and the world is created, or imported if its directory is already there.',
                            'Remove a table and it stops loading. The files stay put, and putting the table back loads it exactly as it was.',
                            'linked_to gives one server several complete sets of dimensions, each with its own Nether and End, portals included, with nothing rewriting portal travel.',
                            'Every world carries its own settings.toml inside its directory, so it is copied, moved and deleted along with the world it describes.',
                            'Anvil regions are read and written back as Linear. Migrating a world is copy the folder and start, never run a converter first.',
                        ] as $point)
                            <li class="flex gap-3">
                                <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="self-center">
                    <x-code lang="toml" file="config/worlds.toml">
[worlds.world]
default = true
type = "normal"
dimension = "overworld"

[worlds.world_nether]
type = "normal"
dimension = "nether"
linked_to = "world"

[worlds.lobby]
type = "void"

[worlds.creative]
type = "flat"
seed = 42
                    </x-code>
                    <p class="mt-5 text-sm leading-relaxed text-muted">
                        Four worlds, one file. <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.85em] text-white/70">normal</code>,
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.85em] text-white/70">flat</code> and
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.85em] text-white/70">void</code>
                        for the hub that should not generate terrain it will never show.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div>
                <div class="grid gap-12 md:grid-cols-2">
                    <div>
                        <span class="pill">Player data</span>
                        <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Your worlds are not your player database</h2>
                        <p class="mt-5 leading-relaxed text-soft">
                            Vanilla keeps <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">playerdata/&lt;uuid&gt;.dat</code>
                            inside the world folder, which is why resetting a map has always
                            been more dangerous than it sounds. Basalt keeps
                            <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">players.db</code>
                            beside <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">worlds/</code>,
                            never inside one.
                        </p>
                        <p class="mt-4 leading-relaxed text-soft">
                            Reset the map, copy a world to a test server, delete a dimension
                            outright: nobody loses their group, their whitelist entry or
                            anything else the server knows about them.
                        </p>
                    </div>

                    <div class="self-center">
                        <div class="mb-6 rounded-2xl border border-white/8 bg-white/4 p-6">
                            <p class="text-[.68rem] font-semibold tracking-[.16em] text-muted uppercase">The rule that decides it</p>
                            <p class="mt-3 text-lg leading-snug text-white/85">
                                Configuration is what the operator writes. State is what the
                                server writes.
                            </p>
                        </div>
                        <ul class="space-y-3.5 text-sm text-soft">
                            @foreach ([
                                'Configuration lives in config/. State lives in players.db or in the world directory. Nothing sits in both.',
                                'There is no operator list in a configuration file. /op writes to the database, so no command ever rewrites a file you are editing.',
                                'groups.toml says what a group is. players.db says who is in it.',
                            ] as $point)
                                <li class="flex gap-3">
                                    <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
                <span class="pill">Server UI</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">
                    Tab list, scoreboard and boss bar, out of the box
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-soft">
                    The things every server ends up installing three plugins for are files
                    in <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.85em] text-white/70">config/</code>,
                    and they all speak the same language.
                </p>
            </div>

            <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
                <div>
                    <x-code lang="toml" file="config/tab_list.toml">
[profiles.default]
default = true
header = [
    "<bold><gradient:gray:white>BASALT</gradient></bold>",
    "<gray>%basalt_animation_website%</gray>",
]
footer = [
    "<gray>TPS <white>%basalt_server_tps%</white></gray>",
    "<gray>Players <white>%basalt_server_online%</white></gray>",
]

[profiles.staff]
priority = 10
when = ["%basalt_player_is_op% = true"]
header = ["<red>STAFF</red>"]
                    </x-code>
                    <p class="mt-5 text-sm leading-relaxed text-muted">
                        Profiles work the same way in every file that draws something: the
                        highest priority whose conditions hold wins, and whatever it leaves
                        out it takes from the default.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    @foreach ([
                        ['One placeholder vocabulary', 'The same %basalt_server_*% and %basalt_player_*% names work in the server list, the tab list, the scoreboard, the boss bar and the messages. Plugins register theirs into the same registry, so a plugin\'s placeholders work everywhere the built-in ones do, with nothing to install in between.'],
                        ['Animations, defined once', 'A list of frames in animations.toml becomes %basalt_animation_<name>% everywhere. The frame comes from the clock, not a per-player counter, so everyone online sees the same one and a player joining halfway through does not restart it.'],
                        ['It only sends what changed', 'A redraw becomes a packet when the text it produced is actually different. A static panel costs nothing however often it is drawn.'],
                    ] as $index => [$heading, $copy])
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-white/90">{{ $heading }}</h3>
                            <p class="mt-2.5 text-sm leading-relaxed text-soft">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="glass mt-5 rounded-2xl p-7">
                <p class="text-sm leading-relaxed text-soft">
                    <span class="font-medium text-white/85">The defaults have an opinion.</span>
                    The tab list ships on, because a header and a footer are decoration and
                    cannot get in the way. The scoreboard and the boss bar ship off, because
                    they cover part of the screen and that is the operator's call to make,
                    not a default to inherit. The tab list is also Java only, and the file
                    says so: a Bedrock player is not missing something that failed to send,
                    their game has nowhere to put it.
                </p>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
                <div>
                    <span class="pill">Permissions</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Groups that do what they read like</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Dotted names, a trailing
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">*</code>
                        to match everything below, a leading
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">-</code>
                        to deny. The most specific rule wins no matter what order they are
                        written in, and anything nobody grants is denied.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        There is no hidden ranking behind the groups and no permission a
                        group gets for being called something. What one can do is exactly
                        what is written, and groups inherit from each other to add or take
                        away from what they got.
                    </p>
                </div>

                <div class="self-center">
                    <x-code lang="toml" file="config/groups.toml">
[groups.moderator]
inherits = "default"
permissions = [
    "basalt.command.kick",
    "basalt.command.teleport",
    "-basalt.command.seed",
]
                    </x-code>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
                <span class="pill">Commands</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Rename it, retire it, reword it</h2>
                <p class="mt-5 text-lg leading-relaxed text-soft">
                    One file decides what a command asks for, what it is called, whether it
                    exists at all and what it answers.
                </p>
            </div>

            <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
                <div>
                    <x-code lang="toml" file="config/commands.toml">
[commands.teleport]
permission = "basalt.command.moderator"
aliases = ["tpa"]

[commands.kill]
enabled = false

[messages]
"commands.seed.success" = "<gray>Seed: %1%</gray>"
                    </x-code>
                </div>

                <ul class="space-y-3.5 self-center text-sm text-soft">
                    @foreach ([
                        ['Point it at a permission', 'Aim a command at one your moderators already hold instead of granting a new one. commands.toml names the permission; groups.toml decides who has it, and the two read in one direction only.'],
                        ['Turn it off', 'A disabled command is not registered, so it does not exist rather than refusing when someone runs it.'],
                        ['Give it other names', 'Aliases on anything, including commands that already shipped with their own.'],
                        ['Rewrite what it says', 'Replace any answer the server sends, keyed by the translation key it normally uses, with %1% and %2% for the values it was going to fill in.'],
                    ] as [$heading, $copy])
                        <li class="flex gap-3">
                            <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                            <span><span class="text-white/85">{{ $heading }}.</span> {{ $copy }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="glass mt-5 rounded-2xl p-7">
                <p class="text-sm leading-relaxed text-soft">
                    <span class="font-medium text-white/85">Worth knowing before you reword anything.</span>
                    By default the server sends the translation key and every client renders
                    it in its own language. Override one and it becomes literal text, the
                    same for everyone, in whatever language you wrote it. That is the trade,
                    and it is why there are no per-language tables: a server is normally
                    aimed at one community rather than being multilingual.
                </p>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Configuration</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">One file per domain, not one file for everything</h2>
                    <div class="mt-6">
                        <x-code>
config/
  server.toml        network, logging, performance
  worlds.toml        which worlds load, and how
  groups.toml        permission groups
  commands.toml      permissions, aliases, wording
  server_list.toml   how the server looks in the list
  tab_list.toml      the panel behind Tab
  scoreboard.toml    the panel down the side
  boss_bar.toml      the bar across the top
  animations.toml    text that moves, shared by the rest
                        </x-code>
                    </div>
                    <p class="mt-5 leading-relaxed text-soft">
                        Generated with comments and vanilla defaults on first start. A
                        <code class="rounded border border-white/8 bg-white/5 px-1.5 py-0.5 font-mono text-[.8em] text-white/70">server.properties</code>
                        found on first start is imported once, so moving an existing server
                        across is a copy.
                    </p>
                </div>

                <div>
                    <span class="pill">Not a port</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Nothing here is inherited by accident</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Parity governs how the game behaves. It does not govern the shape of
                        the software around it, and Basalt carries none of the fifteen years
                        of accumulated decisions that a fork inherits whether it wants them
                        or not.
                    </p>
                    <p class="mt-4 leading-relaxed text-soft">
                        The slot count ships at 80 rather than 20, because 20 is a number
                        from 2010 and nothing in the server cares. What the list advertises
                        is a separate setting, since a profile can show whatever it likes
                        without changing who actually gets in. A world is a dimension. Player
                        state is not filed under a map. None of these are fixes applied on
                        top of something older: there was nothing older.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <span class="pill">Game assets</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Nothing proprietary ships with it</h2>
                <p class="mt-5 leading-relaxed text-soft">
                    Basalt Server does not contain or distribute any proprietary Minecraft
                    content: no structures, no loot tables, no datapacks, no textures, no
                    jars.
                </p>
                <p class="mt-4 leading-relaxed text-soft">
                    Once you accept the
                    <a href="https://www.minecraft.net/en-us/eula" target="_blank" rel="noopener noreferrer" class="text-white/75 underline decoration-white/25 underline-offset-4 transition-colors hover:decoration-white/70">Minecraft EULA</a>,
                    the server downloads the official server archive from Mojang, verifies
                    its SHA-1 and unpacks only the data it needs into a local cache. That
                    happens once per version; later starts touch neither the network nor the
                    archive.
                </p>
            </div>
        </div>
    </section>
    <section class="px-4 pt-8 pb-20">
        <div class="mx-auto max-w-4xl">
            {{-- Same panel as the call to action on the projects page. --}}
            <div class="glass rounded-3xl p-8 text-center md:p-14">
                <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">
                    Ready when you are.
                </h2>
                <p class="mx-auto mt-5 max-w-xl leading-relaxed text-soft">
                    One binary, no runtime to install beside it, and a config folder that explains itself on the first start.
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    {{-- Back up to the downloads rather than straight to a file: the arrow
                         points the way it actually goes. --}}
                    <a href="#downloads" class="btn btn-primary">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 13V4M4.5 7.5 8 4l3.5 3.5"/>
                        </svg>
                        Download Basalt Server
                    </a>
                    <a href="{{ route('docs.show', ['section' => 'server', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Read the docs</a>
                </div>
            </div>
        </div>
    </section>
</div>
