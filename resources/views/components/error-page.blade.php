@props(['code', 'title', 'message'])

<x-layouts::app :title="$code.' '.$title">
    <section class="px-4">
        <div class="mx-auto flex min-h-[70vh] max-w-2xl flex-col items-center justify-center py-24 text-center">
            <p class="text-[clamp(5rem,16vw,9rem)] leading-none font-semibold tracking-[-.05em] text-magma">{{ $code }}</p>
            <h1 class="text-crystal mt-6 text-[clamp(1.7rem,3.6vw,2.4rem)] leading-tight font-semibold tracking-[-.025em]">{{ $title }}</h1>
            <p class="mt-4 max-w-md leading-relaxed text-soft">{{ $message }}</p>

            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn btn-primary">Back to home</a>
                <a href="{{ route('docs') }}" class="btn btn-ghost">Read the docs</a>
            </div>
        </div>
    </section>
</x-layouts::app>
