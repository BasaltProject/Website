<?php

namespace App\Support;

/** How the timings viewer prints numbers, so every page of it agrees. */
class TimingsFormat
{
    public static function millis(float|int|null $value): string
    {
        if ($value === null) {
            return '-';
        }

        if ($value >= 1000) {
            return number_format($value / 1000, 2).' s';
        }

        return number_format($value, $value >= 100 ? 0 : ($value >= 10 ? 1 : 2)).' ms';
    }

    public static function bytes(float|int|null $value): string
    {
        if ($value === null) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $value = (float) $value;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1).' '.$units[$index];
    }

    public static function duration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        if ($seconds < 3600) {
            return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        if ($seconds < 86400) {
            return intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m';
        }

        return intdiv($seconds, 86400).'d '.intdiv($seconds % 86400, 3600).'h';
    }

    /** Tailwind colour for a tick rate: full speed reads calm, a stall reads red. */
    public static function tpsTone(float $tps): string
    {
        return $tps >= 19 ? 'text-emerald-300' : ($tps >= 15 ? 'text-amber-300' : 'text-rose-400');
    }

    /** Tailwind colour for a tick duration against the 50 ms a tick has. */
    public static function msptTone(float $millis): string
    {
        return $millis <= 35 ? 'text-emerald-300' : ($millis <= 50 ? 'text-amber-300' : 'text-rose-400');
    }
}
