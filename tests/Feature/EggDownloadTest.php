<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EggDownloadTest extends TestCase
{
    private const RAW = 'raw.githubusercontent.com/*';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function egg(string $name = 'Basalt Server'): string
    {
        return json_encode(['meta' => ['version' => 'PTDL_v2'], 'name' => $name]);
    }

    public function test_downloads_the_egg_as_an_attachment(): void
    {
        Http::fake([self::RAW => Http::response($this->egg())]);

        $response = $this->get('/projects/server/egg');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="egg-basalt-server.json"');
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('Basalt Server', json_decode($response->getContent(), true)['name']);
    }

    public function test_invalid_response_is_not_cached(): void
    {
        Http::fake([self::RAW => Http::response('<html>nope</html>')]);

        $this->get('/projects/server/egg')->assertRedirect();
        $this->assertNull(Cache::get('eggs.json.server'));
        $this->assertNull(Cache::get('eggs.json.stale.server'));
    }

    public function test_serves_last_good_copy_when_github_fails(): void
    {
        Http::fake([self::RAW => Http::response($this->egg())]);
        $this->get('/projects/proxy/egg')->assertOk();

        Cache::forget('eggs.json.proxy');
        Http::fake([self::RAW => Http::response('', 500)]);

        $this->get('/projects/proxy/egg')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="egg-basalt-proxy.json"');
    }

    public function test_redirects_to_github_when_there_is_no_copy(): void
    {
        Http::fake([self::RAW => Http::response('', 500)]);

        $this->get('/projects/server/egg')
            ->assertRedirect('https://github.com/BasaltProject/Deploy/blob/main/pterodactyl/egg-basalt-server.json');
    }

    public function test_unknown_project_is_404(): void
    {
        $this->get('/projects/nope/egg')->assertNotFound();
    }
}
