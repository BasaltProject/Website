<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Plugins')] class extends Component
{
};
?>

<div class="px-4">
    <div class="mx-auto flex min-h-[62vh] max-w-2xl items-center justify-center py-24">
        <div class="glass w-full overflow-hidden rounded-3xl p-10 text-center md:p-14">
            <span class="absolute inset-x-0 top-0 h-0.5 bg-magma" aria-hidden="true"></span>
            <h1 class="text-crystal text-[clamp(2.4rem,6vw,4rem)] leading-[1.05] font-semibold tracking-[-.035em]">
                <span class="text-magma">Plugins</span>
            </h1>

            <p class="soon-pill mt-8">Coming Soon</p>

            <p class="mt-10 leading-relaxed text-muted">
                This is where community plugins will be published and downloaded.
            </p>
        </div>
    </div>
</div>
