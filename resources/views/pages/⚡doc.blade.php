<?php

use App\Support\Docs;
use Illuminate\Support\Collection;
use App\Support\Mdx;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $section = '';

    public string $slug = '';

    public function mount(string $section, string $slug): void
    {
        abort_unless(Docs::find($section, $slug) !== null, 404);

        $this->section = $section;
        $this->slug = $slug;
    }

    #[Computed]
    public function page(): array
    {
        return Docs::find($this->section, $this->slug) ?? abort(404);
    }

    /** @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>} */
    #[Computed]
    public function rendered(): array
    {
        return Mdx::renderWithHeadings($this->page['source']);
    }

    /** The current section's pages as collapsible groups. */
    #[Computed]
    public function groups(): Collection
    {
        return Docs::groups($this->section);
    }

    /** Previous and next page, kept inside the current section. */
    #[Computed]
    public function neighbours(): array
    {
        $pages = Docs::section($this->section);
        $index = $pages->search(fn (array $page) => $page['slug'] === $this->slug);

        return [
            'previous' => $index > 0 ? $pages[$index - 1] : null,
            'next' => $pages[$index + 1] ?? null,
        ];
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->page['title']);

        if ($this->page['description'] !== '') {
            $view->layoutData(['description' => $this->page['description']]);
        }
    }
};
?>

@php
    $sections = \App\Support\Docs::SECTIONS;
    $accent = $section === 'proxy'
        ? ['bar' => 'border-warped', 'tab' => 'border-b-warped', 'item' => 'border-l-warped', 'toc' => 'aria-[current=location]:border-l-warped']
        : ['bar' => 'border-crimson', 'tab' => 'border-b-crimson', 'item' => 'border-l-crimson', 'toc' => 'aria-[current=location]:border-l-crimson'];
    $headings = $this->rendered['headings'];
    $currentGroup = $this->page['group'];
@endphp

<div x-data="{ open: false }" x-on:docs-open-menu.window="open = true">
    {{-- Below lg: a bar that opens the sidebar. --}}
    <div class="sticky top-[73px] z-30 flex items-center gap-3 border-b border-line bg-base px-4 py-2.5 lg:hidden">
        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-controls="docs-sidebar"
                class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink transition-colors hover:border-line-strong hover:bg-raised">
            <svg viewBox="0 0 16 16" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M2 4h12M2 8h12M2 12h12"/></svg>
            Menu
        </button>
        <span class="min-w-0 truncate text-sm text-muted">{{ $sections[$section]['title'] }}</span>
    </div>

    <div class="mx-auto grid max-w-[90rem] lg:grid-cols-[17rem_minmax(0,1fr)] xl:grid-cols-[17rem_minmax(0,1fr)_14rem]">
        <aside id="docs-sidebar"
               x-bind:class="open && '!flex'"
               class="z-30 hidden max-h-[75dvh] flex-col lg:flex border-b border-line bg-base lg:sticky lg:top-[73px] lg:h-[calc(100dvh-73px)] lg:max-h-none lg:self-start lg:border-r lg:border-b-0">
            <div class="shrink-0 space-y-3 p-4">
                <div class="grid grid-cols-2 gap-1 rounded-lg border border-line bg-surface p-1" role="tablist" aria-label="Documentation section">
                    @foreach ($sections as $key => $info)
                        @php $first = \App\Support\Docs::first($key); @endphp
                        <a href="{{ $first['url'] ?? route('docs') }}" wire:navigate role="tab" aria-selected="{{ $key === $section ? 'true' : 'false' }}"
                           @class([
                               'rounded-md border-b-2 px-2 py-1.5 text-center text-sm font-medium transition-colors',
                               'bg-raised text-ink '.($key === 'proxy' ? 'border-b-warped' : 'border-b-crimson') => $key === $section,
                               'border-b-surface text-muted hover:text-ink' => $key !== $section,
                           ])>{{ str_replace('Basalt ', '', $info['title']) }}</a>
                    @endforeach
                </div>

                <livewire:docs-search variant="sidebar" />
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto border-t border-line px-4 py-4" aria-label="{{ $sections[$section]['title'] }}" data-sidebar-scroll data-section="{{ $section }}">
                @foreach ($this->groups as $group)
                    @php $groupOpen = $group['pages']->contains(fn ($p) => $p['slug'] === $slug); @endphp
                    <div class="mb-1" x-data="{ open: {{ $groupOpen ? 'true' : 'false' }} }">
                        @if ($group['title'] !== '')
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()"
                                    class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-[.92rem] font-semibold text-ink transition-colors hover:bg-surface">
                                {{ $group['title'] }}
                                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-muted transition-transform duration-150" x-bind:class="open && 'rotate-90'" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
                            </button>
                        @endif
                        <ul class="mt-0.5 space-y-px" @if ($group['title'] !== '') x-show="open" @if (! $groupOpen) x-cloak @endif @endif>
                            @foreach ($group['pages'] as $page)
                                @php $active = $page['slug'] === $slug; @endphp
                                <li>
                                    <a href="{{ $page['url'] }}" wire:navigate @if ($active) aria-current="page" data-active @endif
                                       @class([
                                           'block rounded-r-md border-l-2 py-1.5 pr-2 pl-3 text-sm transition-colors',
                                           'bg-raised text-ink '.$accent['item'] => $active,
                                           'border-l-base text-soft hover:bg-surface hover:text-ink' => ! $active,
                                       ])>{{ $page['title'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <article class="min-w-0 px-4 pt-8 pb-16 sm:px-8 lg:px-12 lg:pt-10">
            <div class="mx-auto max-w-[46rem]">
                <nav aria-label="Breadcrumb" class="mb-5 text-sm text-muted">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li><a href="{{ route('docs') }}" wire:navigate class="transition-colors hover:text-ink">Docs</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ \App\Support\Docs::first($section)['url'] ?? route('docs') }}" wire:navigate class="transition-colors hover:text-ink">{{ $sections[$section]['title'] }}</a></li>
                        @if ($currentGroup !== '')
                            <li aria-hidden="true">/</li>
                            <li>{{ $currentGroup }}</li>
                        @endif
                        <li aria-hidden="true">/</li>
                        <li class="text-soft" aria-current="page">{{ $this->page['title'] }}</li>
                    </ol>
                </nav>

                <header>
                    <h1 class="text-[2.25rem] leading-[1.15] font-semibold tracking-[-.02em] text-ink">{{ $this->page['title'] }}</h1>
                    @if ($this->page['description'] !== '')
                        <p class="mt-4 text-lg leading-relaxed text-soft">{{ $this->page['description'] }}</p>
                    @endif
                </header>

                @if (count($headings) > 0)
                    <div class="mt-7 rounded-lg border border-line bg-surface xl:hidden" x-data="{ tocOpen: false }">
                        <button type="button" x-on:click="tocOpen = ! tocOpen" x-bind:aria-expanded="tocOpen.toString()"
                                class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-ink">
                            On this page
                            <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-muted transition-transform duration-150" x-bind:class="tocOpen && 'rotate-90'" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>
                        </button>
                        <ul x-show="tocOpen" x-cloak class="border-t border-line px-4 py-3" data-toc>
                            @foreach ($headings as $heading)
                                <li>
                                    <a href="#{{ $heading['id'] }}" data-toc-link x-on:click="tocOpen = false"
                                       @class([
                                           'block border-l-2 border-l-line py-1 text-sm text-soft transition-colors hover:text-ink aria-[current=location]:text-ink '.$accent['toc'],
                                           'pl-3' => $heading['level'] === 2,
                                           'pl-6' => $heading['level'] === 3,
                                       ])>{{ $heading['text'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="prose-basalt mt-8">
                    {!! $this->rendered['html'] !!}
                </div>

                @php $neighbours = $this->neighbours; @endphp
                @if ($neighbours['previous'] || $neighbours['next'])
                    <nav class="mt-16 grid gap-3 sm:grid-cols-2" aria-label="Pagination">
                        @foreach (['previous' => 'Previous', 'next' => 'Next'] as $key => $label)
                            @if ($neighbours[$key])
                                <a href="{{ $neighbours[$key]['url'] }}" wire:navigate
                                   @class(['block rounded-lg border border-line px-4 py-3.5 transition-colors hover:border-line-strong hover:bg-surface', 'sm:col-start-2 sm:text-right' => $key === 'next'])>
                                    <span class="text-xs text-muted">{{ $label }}</span>
                                    <span class="mt-1 block font-medium text-ink">{{ $neighbours[$key]['title'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                @endif

                <p class="mt-8 border-t border-line pt-6 text-sm text-muted">
                    <a href="{{ $this->page['edit_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 transition-colors hover:text-ink">
                        <x-icon.github width="13" height="13" />
                        Edit this page
                    </a>
                </p>
            </div>
        </article>

        @if (count($headings) > 0)
            <aside class="hidden xl:sticky xl:top-[73px] xl:block xl:max-h-[calc(100dvh-73px)] xl:self-start xl:overflow-y-auto xl:py-10 xl:pr-4" aria-label="On this page">
                <p class="mb-3 text-sm font-semibold text-ink">On this page</p>
                <ul data-toc>
                    @foreach ($headings as $heading)
                        <li>
                            <a href="#{{ $heading['id'] }}" data-toc-link
                               @class([
                                   'block border-l-2 border-l-line py-1 text-[.85rem] leading-snug text-muted transition-colors hover:text-ink aria-[current=location]:text-ink '.$accent['toc'],
                                   'pl-3' => $heading['level'] === 2,
                                   'pl-6' => $heading['level'] === 3,
                               ])>{{ $heading['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</div>
