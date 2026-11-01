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
            <div class="max-w-3xl">
                <span class="pill">Documentation</span>
                <h1 class="text-crystal mt-7 text-[clamp(2.3rem,5.5vw,3.8rem)] leading-[1.06] font-semibold tracking-[-.03em]">
                    Everything, written down.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-soft">
                    How to run Basalt, configure it and write plugins for it. Start with the
                    introduction for the component you are installing, or search.
                </p>
            </div>

            <div class="mt-10 max-w-3xl">
                <livewire:docs-search />
            </div>
        </div>
    </section>

    <section class="px-4 pb-16">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($this->tree as $index => $section)
                    <article class="glass card flex flex-col p-8 md:p-10">
                        <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ $section['title'] }}</h2>
                        <p class="mt-1.5 text-sm text-muted">{{ $section['summary'] }}</p>

                        <ul class="mt-7 divide-y divide-line border-t border-line">
                            @foreach ($section['pages'] as $page)
                                @if ($page['group'] !== '' && $page['group'] !== ($section['pages'][$loop->index - 1]['group'] ?? null))
                                    <li class="pt-6 pb-2 text-[.66rem] font-semibold tracking-[.16em] text-muted uppercase">{{ $page['group'] }}</li>
                                @endif
                                <li>
                                    <a href="{{ $page['url'] }}" wire:navigate class="group flex items-center gap-4 py-3.5 transition-colors hover:text-white">
                                        <span class="text-sm text-soft transition-colors group-hover:text-white">{{ $page['title'] }}</span>
                                        <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 text-line-strong transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-soft" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="glass mt-5 flex flex-col gap-5 rounded-2xl p-8 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-ink">Improve these pages</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted">
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
