@props(['title' => 'terminal'])

<div {{ $attributes->merge(['class' => 'terminal']) }}>
    <div class="terminal-bar" aria-hidden="true">
        <span class="terminal-dot"></span><span class="terminal-dot"></span><span class="terminal-dot"></span>
        <span class="terminal-title">{{ $title }}</span>
    </div>
    <pre><code>{!! trim($slot) !!}</code></pre>
</div>
