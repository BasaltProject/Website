@props(['type' => 'note', 'title' => null])

@php
    $type = in_array($type, ['note', 'tip', 'warning'], true) ? $type : 'note';
    $title ??= ['note' => 'Note', 'tip' => 'Tip', 'warning' => 'Warning'][$type];
@endphp

<div class="callout callout-{{ $type }}" role="note">
    <p class="callout-title">
        @if ($type === 'warning')
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2 1.5 13.5h13L8 2Z"/><path d="M8 6.5v3M8 11.6v.01"/></svg>
        @elseif ($type === 'tip')
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 10.5c-1.2-.9-2-2.2-2-3.8a4.5 4.5 0 0 1 9 0c0 1.6-.8 2.9-2 3.8V12h-5v-1.5Z"/><path d="M6.5 14.5h3"/></svg>
        @else
            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="6.2"/><path d="M8 7.2V11M8 4.9v.01"/></svg>
        @endif
        {{ $title }}
    </p>
    <div class="callout-body">{!! \Illuminate\Support\Str::markdown(trim($slot)) !!}</div>
</div>
