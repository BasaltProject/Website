@props(['label' => 'More information'])

{{-- A caveat that would cost a paragraph, spent as a marker instead. --}}
<span {{ $attributes->merge(['class' => 'info']) }} tabindex="0" role="note" aria-label="{{ $label }}">
    ?
    <span class="info-text">{{ $slot }}</span>
</span>
