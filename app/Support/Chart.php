<?php

namespace App\Support;

/**
 * Bar charts for documentation, written as a fenced `chart` block and drawn as
 * plain HTML and CSS: no script, readable as text, and styled by the site.
 *
 * Header lines are `key: value` (title, note, unit, scale, scope, colors), then a line
 * with `---`, then groups separated by a blank line. A group is an optional name
 * line followed by `series: value` lines. Series keep one colour across groups, and `colors: A=0; B=3`
 * pins a series to a palette slot so the same name has the same colour in every chart.
 */
class Chart
{
    protected const PALETTE = 10;

    /** The chart as HTML, or null when the block is not a valid chart. */
    public static function render(string $source): ?string
    {
        $parsed = self::parse($source);

        if ($parsed === null) {
            return null;
        }

        ['options' => $options, 'groups' => $groups, 'series' => $series] = $parsed;

        $log = ($options['scale'] ?? 'linear') === 'log';
        $perGroup = ($options['scope'] ?? 'chart') === 'group';
        $unit = $options['unit'] ?? '';
        $colours = self::colours($options['colors'] ?? '', $series);
        $named = collect($groups)->contains(fn (array $g) => $g['name'] !== null);

        $all = collect($groups)->flatMap(fn (array $g) => array_column($g['rows'], 'value'));
        $note = trim(($options['note'] ?? '').($log ? ' Logarithmic scale.' : ''));

        $html = '<figure class="chart">';

        if (isset($options['title']) || $note !== '') {
            $html .= '<figcaption class="chart-head">'
                .(isset($options['title']) ? '<span class="chart-title">'.e($options['title']).'</span>' : '')
                .($note !== '' ? '<span class="chart-note">'.e($note).'</span>' : '')
                .'</figcaption>';
        }

        if ($named && count($series) > 1) {
            $html .= '<ul class="chart-legend">';

            foreach ($series as $name) {
                $html .= '<li><span class="chart-swatch chart-c'.$colours[$name].'"></span>'.e($name).'</li>';
            }

            $html .= '</ul>';
        }

        foreach ($groups as $group) {
            $scope = $perGroup ? collect(array_column($group['rows'], 'value')) : $all;
            $html .= '<div class="chart-group">';

            if ($group['name'] !== null) {
                $html .= '<div class="chart-group-name">'.e($group['name']).'</div>';
            }

            foreach ($group['rows'] as $row) {
                $width = self::width($row['value'], $scope->min(), $scope->max(), $log);
                $colour = $colours[$row['series']];

                $html .= '<div class="chart-row"><span class="chart-label">'.e($row['series']).'</span>'
                    .'<span class="chart-track"><span class="chart-bar chart-c'.$colour.'" style="width:'
                    .number_format($width, 2, '.', '').'%"></span></span>'
                    .'<span class="chart-value">'.self::format($row['value']).($unit !== '' ? ' '.e($unit) : '').'</span></div>';
            }

            $html .= '</div>';
        }

        return $html.'</figure>';
    }

    /** @return array{options: array<string, string>, groups: array<int, array{name: ?string, rows: array<int, array{series: string, value: float}>}>, series: array<int, string>}|null */
    protected static function parse(string $source): ?array
    {
        $parts = preg_split('/^---\s*$/m', trim($source), 2);

        if (count($parts) !== 2) {
            return null;
        }

        $options = [];

        foreach (preg_split('/\R/', trim($parts[0])) as $line) {
            if (preg_match('/^([a-z]+):\s*(.+)$/', trim($line), $m)) {
                $options[$m[1]] = $m[2];
            }
        }

        $groups = [];
        $series = [];

        foreach (preg_split('/\R\s*\R/', trim($parts[1])) as $block) {
            $name = null;
            $rows = [];

            foreach (preg_split('/\R/', trim($block)) as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if (preg_match('/^(.+?):\s*(-?\d+(?:\.\d+)?)$/', $line, $m)) {
                    $rows[] = ['series' => $m[1], 'value' => (float) $m[2]];

                    if (! in_array($m[1], $series, true)) {
                        $series[] = $m[1];
                    }
                } elseif ($name === null && $rows === []) {
                    $name = $line;
                } else {
                    return null;
                }
            }

            if ($rows !== []) {
                $groups[] = ['name' => $name, 'rows' => $rows];
            }
        }

        return $groups === [] ? null : ['options' => $options, 'groups' => $groups, 'series' => $series];
    }

    /**
     * @param  array<int, string>  $series
     * @return array<string, int>
     */
    protected static function colours(string $spec, array $series): array
    {
        $pinned = [];

        foreach (explode(';', $spec) as $pair) {
            if (preg_match('/^\s*(.+?)\s*=\s*(\d+)\s*$/', $pair, $m)) {
                $pinned[$m[1]] = (int) $m[2] % self::PALETTE;
            }
        }

        $free = array_values(array_diff(range(0, self::PALETTE - 1), $pinned));
        $colours = [];

        foreach ($series as $name) {
            $colours[$name] = $pinned[$name] ?? array_shift($free) ?? 0;
        }

        return $colours;
    }

    protected static function width(float $value, float $min, float $max, bool $log): float
    {
        if ($max <= 0) {
            return 0;
        }

        if (! $log || $min <= 0) {
            return max(1.5, $value / $max * 100);
        }

        $floor = log10($min) - 0.35;
        $span = log10($max) - $floor;

        return $span <= 0 ? 100 : max(1.5, (log10(max($value, $min)) - $floor) / $span * 100);
    }

    protected static function format(float $value): string
    {
        $text = $value >= 1000 || floor($value) === $value
            ? number_format($value, 0, '.', "\u{202F}")
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return e($text);
    }
}
