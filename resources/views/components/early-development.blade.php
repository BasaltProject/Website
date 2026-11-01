@props(['product'])

{{-- Not .glass: its solid fill would override the gold tint. --}}
<div class="mt-9 flex items-start gap-4 rounded-xl border border-gold/40 bg-gold/10 p-5" role="note">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-gold" aria-hidden="true">
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
        <path d="M12 9v4M12 17h.01"/>
    </svg>
    <div>
        <p class="text-[.68rem] font-semibold tracking-[.16em] text-gold uppercase">Early development</p>
        <p class="mt-1.5 text-sm leading-relaxed text-soft">
            {{ $product }} is young and not finished. Expect unexpected behaviour, missing features and
            changes between releases, and keep backups of anything you care about.
        </p>
    </div>
</div>
