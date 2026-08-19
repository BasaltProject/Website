@props(['type' => 'note', 'title' => null])

@php
    $tone = [
        'note' => ['border-white/12', 'text-white/45'],
        'tip' => ['border-[#7fcf9b]/25', 'text-[#7fcf9b]'],
        'warning' => ['border-[#d8c07a]/28', 'text-[#d8c07a]'],
    ][$type] ?? ['border-white/12', 'text-white/45'];
@endphp

<div class="callout glass my-7 rounded-xl border-l-2 p-6 {{ $tone[0] }}">
    @if ($title)
        <p class="mb-2 text-[.68rem] font-semibold tracking-[.16em] uppercase {{ $tone[1] }}">{{ $title }}</p>
    @endif
    <div class="callout-body">{!! \Illuminate\Support\Str::markdown(trim($slot)) !!}</div>
</div>
