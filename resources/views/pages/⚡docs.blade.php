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
                @foreach ($this->tree as $section)
                    @php
                        $accent = $section['key'] === 'proxy' ? ['bar' => 'bg-warped', 'link' => 'hover:border-warped'] : ['bar' => 'bg-magma', 'link' => 'hover:border-magma'];
                        $intro = $section['pages']->first();
                    @endphp
                    <article class="overflow-hidden rounded-xl border border-line bg-surface">
                        <div class="h-0.5 {{ $accent['bar'] }}"></div>
                        <div class="p-7 md:p-8">
                            <h2 class="text-xl font-semibold tracking-tight text-ink">{{ $section['title'] }}</h2>
                            <p class="mt-1.5 text-sm text-muted">{{ $section['summary'] }}</p>

                            <a href="{{ $intro['url'] }}" wire:navigate class="mt-6 flex items-center justify-between gap-4 rounded-lg border border-line bg-raised px-4 py-3 text-sm font-medium text-ink transition-colors {{ $accent['link'] }}">
                                Start with the introduction
                                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
                            </a>

                            <div class="mt-8 gap-x-8 sm:columns-2">
                                @foreach (\App\Support\Docs::groups($section['key']) as $group)
                                    <div class="mb-6 break-inside-avoid">
                                        @if ($group['title'] !== '')
                                            <h3 class="mb-2 text-[.92rem] font-semibold text-ink">{{ $group['title'] }}</h3>
                                        @endif
                                        <ul>
                                            @foreach ($group['pages'] as $page)
                                                <li>
                                                    <a href="{{ $page['url'] }}" wire:navigate class="block py-1 text-sm text-soft transition-colors hover:text-ink">{{ $page['title'] }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
