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
                    A Minecraft proxy rewritten from scratch in C++. Give your network one
                    address, authenticate players and move them between servers, with built-in
                    Java and Bedrock support. One native binary, no external JVM required.
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
                    Download the executable for your platform and run it once to generate
                    config/. Add your backends to servers.toml, choose their forwarding schemes
                    and start the proxy again. No runtime to install beside it.
                </p>
            </div>
            <div class="mt-9">
                <x-download-grid :repo="config('basalt.repos.proxy')" project="proxy" accent="warped" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Your network</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">One front door, your choice of servers</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Put vanilla, Paper, Spigot, Fabric or Basalt Server behind the same proxy.
                        Players join one public address; the proxy handles login and routes them
                        to a backend. Each backend has its own address, protocol and forwarding
                        scheme, so a network can mix server implementations.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Routes are ordered lists. Forced hosts send a domain straight to a game
                        mode, server groups collect several backends under one name, and fallback
                        tries another eligible server when a connection fails. Players can use
                        /server, and staff can use /send to move them between backends.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Optional TCP or Minecraft status checks spot unavailable servers before
                        a player is sent there. They ship off; when enabled, routing skips servers
                        marked down, but tries them anyway if every server in the route is down.
                    </p>
                </div>
                <div class="self-center">
                    <dl class="glass divide-y divide-line overflow-hidden rounded-2xl">
                        @foreach ([
                            ['Core', 'C++, native binary, shared Basalt Wire network library'],
                            ['Backends', 'Mixed server software, per-server forwarding'],
                            ['Routing', 'Ordered fallback, forced hosts and server groups'],
                            ['Plugins', 'WASM, .bppkg packages'],
                            ['Configuration', 'Commented TOML files in config/'],
                            ['Management', 'Console commands and optional HTTP API'],
                            ['License', 'GPL-3.0'],
                        ] as [$label, $value])
                            <div class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:gap-6">
                                <dt class="shrink-0 text-muted sm:w-28">{{ $label }}</dt>
                                <dd class="text-soft">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Plugins</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">WASM plugins for the whole network</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Write plugins in C++, Rust, Zig or another language that targets
                        WebAssembly. Kiln creates, builds, checks and packs them as .bppkg files,
                        with a manifest, a module and resources. The module is portable across
                        Basalt platforms and compiled locally into a cache.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        React to logins, server changes, chat and commands. Choose or refuse a
                        destination, register backends, move players, send plugin messages and
                        build network-wide interfaces. Player handles belong to the proxy session,
                        so changing backend does not invalidate them.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Callbacks run on a dedicated plugin thread, with execution budgets and
                        sandbox limits. The operator grants access to console commands, network
                        operations and packet interception explicitly. A plugin that overruns its
                        time budget or repeatedly fails is disabled until reload.
                    </p>
                </div>
                <div class="self-center">
                    <div class="grid gap-4">
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">A network API</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">Events, commands, scheduled tasks, configuration and persistent player data, plus backend and player administration.</p>
                        </article>
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">Talk to the client</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">Titles, boss bars, tab lists and sidebars, plus dialogs, resource packs, cookies and transfers where the client protocol supports them.</p>
                        </article>
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">Separate packages</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">Proxy plugins use .bppkg; server plugins use .bspkg. Gameplay, worlds and entities stay on the server.</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <span class="pill">Other plugin runtimes</span>
                <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">BungeeCord and Velocity adapters</h2>
                <p class="mt-5 leading-relaxed text-soft">
                    Existing Java plugins have an optional path through Stratum, the
                    compatibility adapters running on Basalt Shim. Shim is embedded in the
                    binary and starts when a Java plugin jar is present, with no external JDK
                    to install.
                </p>
                <p class="mt-5 leading-relaxed text-soft">
                    These adapters are early. They implement parts of the BungeeCord and
                    Velocity APIs; compatibility depends on the plugin and is not a promise
                    that every jar will run unchanged. The native plugin API is WASM.
                </p>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Routing</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">A domain can lead straight to its game mode</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Keep the default route for the main address and send a survival domain
                        directly to survival. A server group expands into its members in the order
                        you wrote them, without trying the same backend twice.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        All destinations are defined in this same file, each under a
                        servers.&lt;name&gt; table. Changing the routes applies to new connections;
                        players already online stay on their current backend.
                    </p>
                </div>
                <div class="self-center">
                    <x-code lang="toml" file="config/servers.toml">
[routing]
try = ["lobbies"]

[forced-hosts]
"survival.example.org" = ["survival", "lobbies"]

[server-groups.lobbies]
servers = ["lobby-1", "lobby-2"]

[health]
enabled = true
mode = "status"
                    </x-code>
                    <p class="mt-5 leading-relaxed text-muted">Route example: define lobby-1, lobby-2 and survival under [servers] to use it.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Identity forwarding</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Match the backend, keep the identity</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Authenticate Java players with Mojang at the proxy, then forward their
                        identity using the scheme each backend expects. Choose Basalt, Velocity,
                        BungeeCord, BungeeGuard, SafeNet or none per server.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Basalt forwarding signs the identity with an Ed25519 key; Velocity uses
                        its shared-secret scheme. The proxy does not retry with a weaker scheme
                        when authentication fails. BungeeCord-style forwarding and offline
                        backends still need a private network or firewall that prevents direct
                        player connections.
                    </p>
                </div>
                <div class="self-center">
                    <x-code lang="toml" file="config/servers.toml">
[servers.survival]
host = "10.0.0.12"
port = 25565
protocol = "auto"
forwarding = "velocity"
secret = "replace-with-your-backend-secret"
                    </x-code>
                    <p class="mt-5 leading-relaxed text-muted">The backend must use the same forwarding scheme and credential.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Client support</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Java and Bedrock, through the same proxy</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        The core follows the current Minecraft Java release, with older clients
                        translated at the edge. Compatibility is checked for each client and
                        backend pair: a route skips backends that cannot serve that player, and
                        an incompatible server change leaves the player where they are.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        With Basalt Server backends, translation can be delegated to the server.
                        Other backends use the proxy's supported translation routes. This does
                        not make every client version compatible with every backend, and modpack
                        compatibility is not guaranteed.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Enable the built-in Bedrock listener to bridge Bedrock players to Java
                        backends, or delegate their sessions to Basalt Server. Bedrock joins the
                        default route rather than forced hosts. Translation and client features
                        have limits; the documentation lists the supported combinations.
                    </p>
                </div>
                <div class="self-center">
                    <dl class="glass divide-y divide-line overflow-hidden rounded-2xl">
                        <div class="px-6 py-5">
                            <dt class="font-semibold text-ink">Java Edition {{ config('basalt.minecraft.current') }}</dt>
                            <dd class="mt-2 leading-relaxed text-soft">Current protocol native, older versions supported through compatible routes.</dd>
                        </div>
                        <div class="px-6 py-5">
                            <dt class="font-semibold text-ink">Bedrock Edition {{ config('basalt.minecraft.bedrock') }}</dt>
                            <dd class="mt-2 leading-relaxed text-soft">Built-in listener, off by default. NetherNet and RakNet transports.</dd>
                        </div>
                    </dl>
                    <a href="{{ route('docs.show', ['section' => 'proxy', 'slug' => 'bedrock-and-versions']) }}" wire:navigate class="mt-5 inline-block text-soft underline decoration-slate underline-offset-4">Client and backend compatibility</a>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Built-in protection</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Check suspicious connections before they reach a backend</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        The antibot tracks origin reputation and detects bursts of connections,
                        failed logins and new addresses. During an attack, a small limbo world on
                        the proxy checks client behaviour before letting a login reach a backend.
                        Trusted and already verified origins avoid repeated checks.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Gravity, movement, keep-alives and client information checks can be
                        combined with a block or map captcha. Configure trusted and blocked IPs
                        or CIDR ranges, blocklists and attack notifications in antibot.toml.
                        This filters Minecraft connections; it does not replace protection
                        against a saturated network link.
                    </p>
                </div>
                <div class="self-center">
                    <div class="grid gap-4">
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">Bounded admission</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">Global and per-origin rate limits, separate status-request budgets, session caps and connection deadlines bound the work a login can create.</p>
                        </article>
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">Backpressure</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">Per-session and global network-buffer budgets limit retained memory. Reads pause when the other end cannot keep up.</p>
                        </article>
                        <article class="glass card p-6">
                            <h3 class="font-semibold tracking-tight text-ink">Behind a load balancer</h3>
                            <p class="mt-2.5 leading-relaxed text-soft">HAProxy PROXY protocol v1 and v2 can preserve the player address, accepted only from explicitly trusted IPs or CIDR ranges.</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Network UI</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">One look across your servers</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Configure the server list, tab header and footer, sidebar and boss bar
                        with the same MiniMessage text, placeholders, animations and conditional
                        profiles. Profiles can change the presentation for a domain, client
                        version or matching player condition.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Server-list profiles can show the proxy's own values or relay selected
                        fields from a backend or route, with cached responses and local values
                        while a backend is unavailable. Tab list, sidebar and boss bar ship off;
                        choose whether the proxy or the backend has priority when both draw UI.
                        Tab decoration is Java only.
                    </p>
                </div>
                <div class="self-center">
                    <x-code lang="toml" file="config/tab_list.toml">
enabled = true

[ui]
priority = "proxy"

[profiles.default]
default = true
header = ["<bold>MY NETWORK</bold>"]
footer = [
    "<gray>Online: <white>%basalt_server_online%</white></gray>",
    "<gray>%basalt_animation_website%</gray>",
]
                    </x-code>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Access and commands</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Manage players across the network</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Groups grant dotted permissions, wildcard rules and explicit denials,
                        with inheritance and the most specific rule winning. Group membership,
                        bans and whitelist entries are player state in .basalt/players.db,
                        separate from the files that define your groups.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Use /glist for the network, /server to choose a backend, and /send to move
                        a player. Staff can ban, pardon, whitelist and kick at the proxy. Change
                        command permissions, aliases and availability in commands.toml, and
                        rewrite replies in messages.toml.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Simple text commands such as /discord or /rules can live in configuration.
                        Proxy commands also have a basalt: prefix so a backend command with the
                        same name does not make them unreachable.
                    </p>
                </div>
                <div class="self-center">
                    <x-code lang="toml" file="config/groups.toml">
[groups.moderator]
inherits = "default"
permissions = [
    "basalt.command.glist",
    "basalt.command.send",
    "basalt.command.kick",
    "basalt.command.ban",
]
                    </x-code>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Operations</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">A console for you, an API for your panel</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        Inspect sessions, traffic, completed logins and backend changes from the
                        console. Logs go to the terminal and rotating files, with compressed
                        archives and configurable retention.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        The optional HTTP API uses a generated bearer token and exposes proxy
                        state plus player send, kick and configuration reload actions. Each
                        action can be disabled for a panel that should only read state. It ships
                        off and binds to loopback by default.
                    </p>
                    <p class="mt-5 leading-relaxed text-soft">
                        Reload supported configuration and plugins from the console, or opt into
                        file watching. Invalid configuration is rejected while the running
                        settings remain in place; listener and runtime changes need a restart.
                    </p>
                </div>
                <div class="self-center">
                    <x-code lang="toml" file="config/remote.toml">
[api]
enabled = true
bind = "127.0.0.1"
port = 25590

[actions]
send = true
kick = false
reload = false
                    </x-code>
                    <p class="mt-5 leading-relaxed text-muted">The token lives in .basalt/api.token. Put TLS in front of the API when exposing it beyond a private network.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <span class="pill">Configuration</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">A file for each part of the network</h2>
                    <p class="mt-5 leading-relaxed text-soft">
                        The proxy generates commented TOML files on first start. Network
                        listeners, routing, protection, permissions and presentation each have
                        their own place, so changing the MOTD does not mean searching through
                        forwarding secrets.
                    </p>
                </div>
                <div class="self-center">
                    <x-code file="config/">
proxy.toml        listeners, authentication, runtime, plugins
servers.toml      backends, routes, health checks
antibot.toml      reputation, limbo, captcha, blocklists
limits.toml       packet and traffic limits
remote.toml       HTTP administration API
groups.toml       permission groups
commands.toml     permissions, aliases, custom commands
messages.toml     player and command messages
server_list.toml  MOTD, profiles, backend status relay
tab_list.toml     tab header and footer
scoreboard.toml   sidebar
boss_bar.toml     boss bar
animations.toml   shared text animations
                    </x-code>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 pt-8 pb-20">
        <div class="mx-auto max-w-4xl">
            <div class="glass rounded-3xl p-8 text-center md:p-14">
                <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">Put your network behind one address.</h2>
                <p class="mx-auto mt-5 max-w-xl leading-relaxed text-soft">
                    One native binary, commented configuration and a plugin API built for the whole network.
                    Basalt Proxy is in early development; test your backends and plugins before a public rollout.
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    <a href="#downloads" class="btn btn-warped">Download Basalt Proxy</a>
                    <a href="{{ route('docs.show', ['section' => 'proxy', 'slug' => 'introduction']) }}" wire:navigate class="btn btn-ghost">Read the docs</a>
                </div>
            </div>
        </div>
    </section>
</div>
