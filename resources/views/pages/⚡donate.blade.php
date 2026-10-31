<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Donate')] class extends Component
{
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-6xl pt-10 pb-16">
            <div class="grid gap-14 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                <div class="reveal">
                    <span class="pill">
                        <x-icon.heart class="text-white/50" />
                        Donate
                    </span>
                    <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                        Pay for the work,<br>not for the software.
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-white/55">
                        Basalt is GPL-3.0 and always will be. There is no paid tier, no
                        licence to buy and nothing held back for people who give. What
                        donations buy is time: the hours that go into a server rewritten from
                        scratch instead of forked, and the machines that build and test it on
                        five platforms.
                    </p>

                </div>

                <div class="reveal lg:sticky lg:top-28 lg:self-start" data-reveal-delay="120">
                    <livewire:donate-amounts />
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 pb-16">
        <div class="mx-auto grid max-w-6xl gap-4 md:grid-cols-3">
            @foreach ([
                ['Development time', 'The parity work is slow by nature: every system is checked against what the official server actually does, not against what seems reasonable.'],
                ['Build and test machines', 'A binary is only proven on the platform it runs on, so every platform needs a host of its own to build on and be tested on.'],
                ['Everything stays free', 'No paid edition, no licence, no feature behind a tier. Donating buys the project time, never you an advantage.'],
            ] as $index => [$heading, $copy])
                <article class="glass card p-7 reveal" data-reveal-delay="{{ $index * 90 }}">
                    <h2 class="font-semibold tracking-tight text-white/90">{{ $heading }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/50">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="px-4 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div class="reveal">
                    <span class="pill">Other ways</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Money is not the only thing that helps</h2>
                    <p class="mt-5 leading-relaxed text-white/55">
                        A parity bug reported with the steps to reproduce it is worth a great
                        deal, because finding one is most of the work of fixing it.
                    </p>
                    <ul class="mt-7 space-y-3.5 text-sm text-white/55">
                        @foreach ([
                            'Report anything that behaves differently from the official server, with the steps that showed it.',
                            'Send a patch. The server, the proxy, the network layer and this site are all public.',
                            'Improve the documentation. It is its own repository and every page has an edit link.',
                            'Run it, and say what broke.',
                        ] as $point)
                            <li class="flex gap-3">
                                <x-icon.check class="mt-0.5 shrink-0 text-white/30" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ config('basalt.github') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost py-2.5 text-sm">
                            <x-icon.github />
                            Browse the source
                        </a>
                        <a href="{{ route('docs') }}" wire:navigate class="btn btn-ghost py-2.5 text-sm">Documentation</a>
                    </div>
                </div>

                <div class="reveal" data-reveal-delay="100">
                    <span class="pill">Companies</span>
                    <h2 class="text-crystal mt-6 text-[clamp(1.6rem,3.2vw,2.2rem)] font-semibold tracking-[-.025em]">Building something on Basalt?</h2>
                    <p class="mt-5 leading-relaxed text-white/55">
                        Hosts, networks and studios that depend on it can sponsor the work
                        directly, through the same sponsorship page.
                    </p>
                    <p class="mt-4 leading-relaxed text-white/55">
                        It does not buy influence over the roadmap or a feature nobody else
                        gets. Vanilla parity is the acceptance criterion whoever is asking.
                    </p>
                    <a href="{{ config('basalt.donate.url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost mt-8 py-2.5 text-sm">
                        <x-icon.heart />
                        Sponsor Basalt
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 pt-8 pb-20">
        <div class="mx-auto max-w-4xl">
            <div class="glass reveal rounded-3xl p-8 text-center md:p-14">
                <h2 class="text-crystal text-[clamp(1.7rem,3.4vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">
                    Thank you, genuinely.
                </h2>
                <p class="mx-auto mt-5 max-w-xl leading-relaxed text-white/50">
                    Donations go through GitHub Sponsors.
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    <a href="{{ config('basalt.donate.url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                        <x-icon.heart />
                        Donate
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
