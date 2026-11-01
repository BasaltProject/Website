@props(['type' => 'note', 'title' => null])

@php
    $tone = [
        'note' => ['border-line-strong', 'text-muted'],
        'tip' => ['border-[#334840]', 'text-[#7fcf9b]'],
        'warning' => ['border-[#56472d]', 'text-gold'],
    ][$type] ?? ['border-line-strong', 'text-muted'];
@endphp

<div class="callout glass my-7 rounded-xl border-l-2 p-6 {{ $tone[0] }}">
    @if ($title)
        <p class="mb-2 text-[.68rem] font-semibold tracking-[.16em] uppercase {{ $tone[1] }}">{{ $title }}</p>
    @endif
    <div class="callout-body">{!! \Illuminate\Support\Str::markdown(trim($slot)) !!}</div>
</div>
