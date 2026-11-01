<?php

namespace Tests\Feature;

use App\Support\Releases;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReleaseHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function release(string $tag, string $date, array $extra = []): array
    {
        return array_merge([
            'tag_name' => $tag,
            'published_at' => $date,
            'html_url' => "https://github.com/BasaltProject/Server/releases/tag/$tag",
            'prerelease' => false,
            'draft' => false,
            'assets' => [
                ['name' => "basalt-$tag-Linux-x64", 'browser_download_url' => "https://example.test/$tag/linux", 'size' => 5242880],
                ['name' => 'SHA256SUMS', 'browser_download_url' => "https://example.test/$tag/sums", 'size' => 100],
            ],
        ], $extra);
    }

    public function test_history_keeps_only_stable_releases_newest_first(): void
    {
        Http::fake(['api.github.com/*' => Http::response([
            $this->release('v1.0.0', '2026-01-01T00:00:00Z'),
            $this->release('nightly', '2026-05-01T00:00:00Z'),
            $this->release('v2.0.0-rc1', '2026-04-01T00:00:00Z', ['prerelease' => true]),
            $this->release('v1.5.0', '2026-03-01T00:00:00Z', ['draft' => true]),
            $this->release('v1.1.0', '2026-02-01T00:00:00Z'),
        ])]);

        $history = Releases::history('https://github.com/BasaltProject/Server');

        $this->assertSame(['1.1.0', '1.0.0'], array_column($history, 'version'));
        $this->assertSame('v1.1.0', $history[0]['tag']);
        $this->assertSame('https://example.test/v1.1.0/sums', $history[0]['checksums']);
        $this->assertSame('Linux', $history[0]['assets'][0]['label']);
        $this->assertSame(5242880, $history[0]['assets'][0]['size']);
        $this->assertSame('https://example.test/v1.1.0/linux', $history[0]['assets'][0]['url']);
    }

    public function test_history_is_empty_when_github_fails(): void
    {
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->assertSame([], Releases::history('https://github.com/BasaltProject/Server'));
    }

    public function test_builds_page_status_codes(): void
    {
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->get('/projects/server/builds')->assertOk()->assertSee('Release history is unavailable right now.');
        $this->get('/projects/nope/builds')->assertNotFound();
    }
}
