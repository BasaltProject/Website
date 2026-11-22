<?php

namespace Tests\Unit;

use App\Support\Chart;
use App\Support\Mdx;
use Tests\TestCase;

class ChartTest extends TestCase
{
    public function test_a_chart_block_is_drawn_as_bars_with_a_legend(): void
    {
        $html = Chart::render("title: Cost\nunit: ns\nscale: log\n---\nRegion\nA: 100\nB: 10000\n\nChat\nA: 50\nB: 5000");

        $this->assertStringContainsString('chart-legend', $html);
        $this->assertSame(4, substr_count($html, 'chart-row'));
        $this->assertStringContainsString("10\u{202F}000 ns", $html);
        $this->assertStringContainsString('Logarithmic scale.', $html);
    }

    public function test_a_malformed_chart_falls_back_to_a_code_block(): void
    {
        $html = Mdx::render("```chart\nnot a chart\n```");

        $this->assertStringContainsString('code-block', $html);
        $this->assertStringNotContainsString('class="chart"', $html);
    }
}
