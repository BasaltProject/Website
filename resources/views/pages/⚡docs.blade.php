<?php

use App\Support\Docs;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Documentation')] class extends Component
{
    #[Computed]
    public function tree(): Collection
    {
        return Docs::tree();
    }
};
?>

<div>
    <section class="px-4">
        <div class="mx-auto max-w-6xl pt-10 pb-14">
            <div class="reveal max-w-3xl">
                <span class="pill">Documentation</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    Everything, written down.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/55">
                    How to run Basalt, configure it and write plugins for it. Start with the
                    introduction for the component you are installing, or search.
                </p>
            </div>

            <div class="reveal mt-10 max-w-3xl" data-reveal-delay="80">
                <livewire:docs-search />
            </div>
        </div>
    </section>

    <section class="px-4 pb-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($this->tree as $index => $section)
                    <article class="glass card flex flex-col p-8 reveal md:p-10" data-reveal-delay="{{ $index * 110 }}">
                        <h2 class="text-2xl font-semibold tracking-tight text-white/95">{{ $section['title'] }}</h2>
                        <p class="mt-1.5 text-sm text-white/40">{{ $section['summary'] }}</p>

                        <ul class="mt-7 divide-y divide-white/6 border-t border-white/6">
                            @foreach ($section['pages'] as $page)
                                <li>
                                    <a href="{{ $page['url'] }}" wire:navigate class="group flex items-center gap-4 py-3.5 transition-colors hover:text-white">
                                        <span class="text-sm text-white/70 transition-colors group-hover:text-white">{{ $page['title'] }}</span>
                                        <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 text-white/20 transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-white/60" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="glass reveal mt-5 flex flex-col gap-5 rounded-2xl p-8 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-white/90">Improve these pages</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-white/45">
                        The documentation is its own repository. Every page has an edit link,
                        and pull requests are welcome.
                    </p>
                </div>
                <a href="{{ config('basalt.repos.documentation') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost shrink-0 py-2.5 text-sm sm:ml-auto">
                    <x-icon.github />
                    Documentation repo
                </a>
            </div>
        </div>
    </section>
</div>
