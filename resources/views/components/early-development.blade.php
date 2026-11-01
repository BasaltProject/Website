@props(['product'])

{{-- Solid gold block with dark text for maximum visibility. --}}
<div class="mt-9 flex items-start gap-4 rounded-xl bg-gold p-6 text-[#14100d]" role="note">
    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#14100d" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true">
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
        <path d="M12 9v4M12 17h.01"/>
    </svg>
    <div>
        <p class="text-[.85rem] font-bold tracking-[.08em] text-[#14100d] uppercase">Early development</p>
        <p class="mt-1.5 text-[.95rem] leading-relaxed font-medium text-[#14100d]">
            {{ $product }} is young and not finished. Expect unexpected behaviour, missing features and
            changes between releases, and keep backups of anything you care about.
        </p>
    </div>
</div>
