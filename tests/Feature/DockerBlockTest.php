<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DockerBlockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function fakeReleases(): void
    {
        $release = fn (string $tag, string $date) => [
            'tag_name' => $tag,
            'published_at' => $date,
            'html_url' => "https://github.com/BasaltProject/Server/releases/tag/$tag",
            'prerelease' => false,
            'draft' => false,
            'assets' => [
                ['name' => "basalt-$tag-Linux-x64", 'browser_download_url' => "https://example.test/$tag/linux", 'size' => 5242880],
            ],
        ];

        Http::fake(['api.github.com/*' => Http::response([
            $release('v26.3.1', '2026-04-01T00:00:00Z'),
            $release('v26.3.0', '2026-03-01T00:00:00Z'),
        ])]);
    }

    public function test_hidden_when_not_published(): void
    {
        config(['basalt.docker.published' => false]);
        $this->fakeReleases();

        $this->get('/projects/server/builds')->assertOk()->assertDontSeeText('docker pull');
    }

    public function test_shown_for_versions_since_first_image(): void
    {
        config(['basalt.docker.published' => true]);
        $this->fakeReleases();

        $this->get('/projects/server/builds')->assertOk()
            ->assertSeeText('docker pull ghcr.io/basaltproject/server:26.3.1')
            ->assertDontSeeText('server:26.3.0');

        Cache::flush();

        $this->get('/projects/proxy/builds')->assertOk()
            ->assertSeeText('docker pull ghcr.io/basaltproject/proxy:26.3.1')
            ->assertDontSeeText('proxy:26.3.0');
    }

    public function test_project_pages_hide_images_when_not_published(): void
    {
        config(['basalt.docker.published' => false]);
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->get('/projects/server')->assertOk()->assertDontSee('ghcr.io/basaltproject');
        $this->get('/projects/proxy')->assertOk()->assertDontSee('ghcr.io/basaltproject');
    }

    public function test_server_page_shows_docker_run_when_published(): void
    {
        config(['basalt.docker.published' => true]);
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->get('/projects/server')->assertOk()->assertSeeText(
            'docker run -it -p 25565:25565 -p 19132:19132/udp -v basalt-server:/data -e EULA=1 ghcr.io/basaltproject/server:latest'
        );
    }

    public function test_proxy_page_shows_docker_run_when_published(): void
    {
        config(['basalt.docker.published' => true]);
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->get('/projects/proxy')->assertOk()
            ->assertSeeText('docker run -it -p 25565:25565 -v basalt-proxy:/data ghcr.io/basaltproject/proxy:latest')
            ->assertDontSeeText('EULA=1');
    }
}
