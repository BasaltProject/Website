@props(['node', 'total', 'depth' => 0])

@php

    $children = $node['children'] ?? [];
    $share = $total > 0 ? ($node['total_millis'] ?? 0) / $total : 0;
    $width = max(min($share * 100, 100), $share > 0 ? 0.6 : 0);
@endphp

@if (count($children) > 0)
    <details @if ($depth < 2 && $share >= 0.02) open @endif data-node>
        <summary class="timings-row cursor-pointer list-none">
            <x-timings.row :node="$node" :share="$share" :width="$width" :depth="$depth" :branch="true" />
        </summary>
        <div class="ml-4 border-l border-white/8 pl-2">
            @foreach ($children as $child)
                <x-timings.node :node="$child" :total="$total" :depth="$depth + 1" />
            @endforeach
        </div>
    </details>
@else
    <div class="timings-row">
        <x-timings.row :node="$node" :share="$share" :width="$width" :depth="$depth" :branch="false" />
    </div>
@endif
