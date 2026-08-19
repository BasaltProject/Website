<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Plugins')] class extends Component
{
};
?>

<div class="px-4">
    <div class="mx-auto flex min-h-[62vh] max-w-2xl flex-col items-center justify-center py-24 text-center">
        <h1 class="text-crystal reveal text-[clamp(2.4rem,6vw,4rem)] leading-[1.05] font-semibold tracking-[-.035em]">
            Plugins
        </h1>

        <p class="soon-pill reveal mt-8" data-reveal-delay="80">Coming Soon</p>

        <p class="reveal mt-10 leading-relaxed text-white/45" data-reveal-delay="160">
            This is where community plugins will be published and downloaded.
        </p>
    </div>
</div>
