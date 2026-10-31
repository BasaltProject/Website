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

    #[Computed]
    public function html(): string
    {
        return Mdx::render($this->page['source']);
    }

    #[Computed]
    public function tree(): Collection
    {
        return Docs::tree();
    }

    /** Previous and next page across the whole documentation, in reading order. */
    #[Computed]
    public function neighbours(): array
    {
        $all = Docs::all()->values();
        $index = $all->search(
            fn (array $page) => $page['section'] === $this->section && $page['slug'] === $this->slug
        );

        return [
            'previous' => $index > 0 ? $all[$index - 1] : null,
            'next' => $all[$index + 1] ?? null,
        ];
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->page['title']);
    }
};
?>

<div class="px-4">
    <div class="mx-auto grid max-w-6xl gap-12 pt-6 pb-16 lg:grid-cols-[15rem_1fr]">
        <aside class="lg:sticky lg:top-28 lg:self-start">
            <a href="{{ route('dev.docs') }}" wire:navigate class="mb-7 inline-flex items-center gap-2 text-sm text-white/40 transition-colors hover:text-white/75">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>
                Documentation
            </a>

            <nav class="space-y-7">
                @foreach ($this->tree as $section)
                    <div>
                        <p class="mb-3 text-[.66rem] font-semibold tracking-[.16em] text-white/35 uppercase">{{ $section['title'] }}</p>
                        <ul class="space-y-0.5 border-l border-white/8">
                            @foreach ($section['pages'] as $page)
                                @php $active = $page['section'] === $this->section && $page['slug'] === $this->slug; @endphp
                                @if ($page['group'] !== '' && $page['group'] !== ($section['pages'][$loop->index - 1]['group'] ?? null))
                                    <li class="-ml-px pt-5 pb-1.5 pl-4 text-[.66rem] font-semibold tracking-[.14em] text-white/55 uppercase">{{ $page['group'] }}</li>
                                @endif
                                <li>
                                    <a href="{{ $page['url'] }}" wire:navigate
                                       @class([
                                           '-ml-px block border-l py-1.5 pl-4 text-sm transition-colors duration-300',
                                           'border-white/70 text-white' => $active,
                                           'border-transparent text-white/45 hover:border-white/25 hover:text-white/80' => ! $active,
                                       ])>{{ $page['title'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <article class="min-w-0">
            <header class="reveal">
                <span class="text-[.66rem] font-semibold tracking-[.16em] text-white/35 uppercase">
                    {{ \App\Support\Docs::SECTIONS[$this->section]['title'] }}
                </span>
                <h1 class="text-crystal mt-4 text-[clamp(2rem,4.5vw,3rem)] leading-[1.1] font-semibold tracking-[-.03em]">
                    {{ $this->page['title'] }}
                </h1>
                @if ($this->page['description'] !== '')
                    <p class="mt-4 text-lg leading-relaxed text-white/50">{{ $this->page['description'] }}</p>
                @endif
                <div class="rule mt-9"></div>
            </header>

            <div class="prose-basalt mt-9">
                {!! $this->html !!}
            </div>

            @php $neighbours = $this->neighbours; @endphp
            @if ($neighbours['previous'] || $neighbours['next'])
                <nav class="mt-16 grid gap-4 sm:grid-cols-2">
                    @foreach (['previous' => 'Previous', 'next' => 'Next'] as $key => $label)
                        @if ($neighbours[$key])
                            <a href="{{ $neighbours[$key]['url'] }}" wire:navigate
                               @class(['glass card block p-5', 'sm:col-start-2 sm:text-right' => $key === 'next'])>
                                <span class="text-[.64rem] font-medium tracking-[.16em] text-white/30 uppercase">{{ $label }}</span>
                                <span class="mt-1.5 block font-medium text-white/85">{{ $neighbours[$key]['title'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            @endif

            <p class="mt-10 text-sm text-white/30">
                <a href="{{ $this->page['edit_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 transition-colors hover:text-white/70">
                    <x-icon.github width="13" height="13" />
                    Edit this page
                </a>
            </p>
        </article>
    </div>
</div>
