<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Open a timings report')] class extends Component
{
};
?>

<div class="px-3">
    <div class="mx-auto max-w-[96rem] pb-16">
        <div class="mb-3 px-1">
            <span class="pill">Timings report</span>
        </div>

        <div wire:ignore data-timings-viewer data-local></div>
    </div>
</div>
