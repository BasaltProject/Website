<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $frequency = 'monthly';

    public ?int $amount = null;

    public function mount(): void
    {
        $this->amount = $this->amounts()[2] ?? null;
    }

    /** @return array<int, int> */
    public function amounts(): array
    {
        return config('basalt.donate.'.($this->frequency === 'monthly' ? 'monthly' : 'once'));
    }

    public function updatedFrequency(): void
    {
        // The scales differ, so a figure carried across would land on nothing.
        $this->amount = $this->amounts()[2] ?? null;
    }

    /** The highest tier the chosen amount reaches. One-time gifts have no tier. */
    #[Computed]
    public function tier(): ?array
    {
        if ($this->frequency !== 'monthly' || $this->amount === null) {
            return null;
        }

        return collect(config('basalt.donate.tiers'))
            ->filter(fn (array $tier) => $tier['from'] <= $this->amount)
            ->sortByDesc('from')
            ->first();
    }
};
?>

@php
    // Falls back to the plain fill when a one-time gift has no tier.
    $gradient = $this->tier['gradient'] ?? null;
@endphp

<div class="glass scroll-mt-28 rounded-3xl p-7 md:p-9" id="support"
     @if ($gradient) style="--tier-from: {{ $gradient[0] }}; --tier-to: {{ $gradient[1] }};" @endif>
    <div class="flex rounded-full border border-line bg-base p-1" role="group" aria-label="Donation frequency">
        @foreach (['monthly' => 'Monthly', 'once' => 'One-time'] as $value => $text)
            <button type="button" wire:click="$set('frequency', '{{ $value }}')"
                    @class([
                        'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-colors duration-150',
                        'bg-raised text-ink' => $frequency === $value,
                        'text-soft hover:text-ink' => $frequency !== $value,
                    ])
                    aria-pressed="{{ $frequency === $value ? 'true' : 'false' }}">{{ $text }}</button>
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-3 gap-2.5" wire:key="amounts-{{ $frequency }}">
        @foreach ($this->amounts() as $value)
            <button type="button" wire:click="$set('amount', {{ $value }})"
                    @class([
                        'rounded-xl border px-3 py-3.5 text-base font-semibold transition-all duration-150',
                        'amount-selected' => $amount === $value && $gradient,
                        'border-muted bg-base text-ink' => $amount === $value && ! $gradient,
                        'border-line bg-raised text-soft hover:border-slate hover:text-ink' => $amount !== $value,
                    ])>{{ config('basalt.donate.currency') }}{{ $value }}</button>
        @endforeach
    </div>

    {{-- The amount is the tier. Showing which one it lands on here is what stops
         this and the cards below being two separate ways to give. --}}
    @if ($this->tier)
        <div class="mt-7" wire:key="tier-{{ $this->tier['name'] }}">
            @php
                // Ash and Obsidian take "an", Slate and Magma take "a".
                $article = str_contains('aeiou', strtolower($this->tier['name'][0])) ? 'an' : 'a';
            @endphp
            <p class="text-center text-soft">
                Become {{ $article }} <span class="tier-name font-semibold">{{ $this->tier['name'] }}</span> supporter
            </p>
            <div class="rule mt-3.5"></div>
            <ul class="mt-4 space-y-2.5 text-sm text-soft">
                @foreach ($this->tier['perks'] as $perk)
                    <li class="flex gap-2.5">
                        <x-icon.check class="mt-0.5 shrink-0 text-muted" width="13" height="13" />
                        <span>{{ $perk }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($frequency === 'once')
        <p class="mt-7 text-center text-sm leading-relaxed text-muted">
            A one-time gift carries no tier and needs no account. It just helps.
        </p>
    @endif

    <a href="{{ config('basalt.donate.url') }}" target="_blank" rel="noopener noreferrer"
       @class(['btn btn-primary mt-6 w-full', 'btn-tier' => $gradient])>
        <x-icon.heart />
        @if ($amount === null)
            Continue
        @elseif ($frequency === 'monthly')
            Give {{ config('basalt.donate.currency') }}{{ $amount }} a month
        @else
            Give {{ config('basalt.donate.currency') }}{{ $amount }}
        @endif
    </a>

    <p class="mt-4 text-center text-xs leading-relaxed text-muted">
        Handled by GitHub Sponsors. Cancel a monthly gift whenever you like.
    </p>
</div>
