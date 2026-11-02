@php
    // Deterministic LCG so the layout is identical on every render.
    $seed = 20240611;
    $rand = function () use (&$seed) {
        $seed = ($seed * 1664525 + 1013904223) % 4294967296;
        return $seed / 4294967296;
    };

    $width = 2400;
    $faces = [];
    $contour = [];
    $x = 0;
    $prev = 70;
    while ($x < $width) {
        $w = (int) round(44 + $rand() * 40);
        do {
            $len = (int) round(28 + $rand() * 84);
        } while (abs($len - $prev) < 18);
        $prev = $len;

        $rand(); // two draws kept (former chamfer and tone) so widths and lengths stay on the same seeded sequence
        $rand();

        $x2 = $x + $w;
        $faces[] = "{$x},0 {$x},{$len} {$x2},{$len} {$x2},0";

        array_push($contour, "{$x},{$len}", "{$x2},{$len}");
        $x += $w;
    }
    $d = 'M' . implode(' L', $contour);
@endphp
<div class="pointer-events-none absolute inset-x-0 top-0 h-[max(5vw,80px)] overflow-hidden" aria-hidden="true">
    <svg {{ $attributes->merge(['class' => 'absolute left-1/2 top-0 block h-auto w-[max(100%,1600px)] -translate-x-1/2']) }}
         style="aspect-ratio: 2400 / 120" viewBox="0 0 {{ $width }} 120" focusable="false">
        <defs>
            <linearGradient id="basalt-edge-line" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="{{ $width }}" y2="0">
                <stop offset="0" style="stop-color: #ef5569"/>
                <stop offset="1" style="stop-color: var(--color-warped)"/>
            </linearGradient>
        </defs>
        <rect x="0" y="-1" width="{{ $width }}" height="24" fill="#111216"/>
        @foreach ($faces as $f)
            <polygon points="{{ $f }}" fill="#111216"/>
        @endforeach
        <path d="{{ $d }}" fill="none" stroke="url(#basalt-edge-line)" stroke-width="1.5" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
    </svg>
</div>
