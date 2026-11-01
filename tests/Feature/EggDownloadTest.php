<?php

namespace Tests\Feature;

use App\Support\Eggs;
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

    private const API = 'api.github.com/repos/BasaltProject/Deploy/commits*';

    private const OLD = 'a1b2c3d4e5f60718293a4b5c6d7e8f9012345678';

    private const NEW = 'ffeeddccbbaa99887766554433221100aabbccdd';

    private function commits(): array
    {
        return [
            ['sha' => self::NEW, 'html_url' => 'https://github.com/BasaltProject/Deploy/commit/'.self::NEW, 'commit' => ['message' => "Newest change\n\nLong body", 'committer' => ['date' => '2026-10-02T10:00:00Z']]],
            ['sha' => self::OLD, 'html_url' => 'https://github.com/BasaltProject/Deploy/commit/'.self::OLD, 'commit' => ['message' => 'Older change', 'committer' => ['date' => '2026-09-01T10:00:00Z']]],
        ];
    }

    public function test_history_keeps_order_and_first_line(): void
    {
        Http::fake([self::API => Http::response($this->commits())]);

        $history = Eggs::history('server');

        $this->assertSame([self::NEW, self::OLD], array_column($history, 'sha'));
        $this->assertSame('ffeeddc', $history[0]['short']);
        $this->assertSame('Newest change', $history[0]['message']);
        $this->assertSame('https://github.com/BasaltProject/Deploy/commit/'.self::NEW, $history[0]['url']);
        $this->assertSame('2026-10-02T10:00:00Z', Eggs::updated('server'));
    }

    public function test_downloads_a_valid_version(): void
    {
        Http::fake([
            self::API => Http::response($this->commits()),
            'raw.githubusercontent.com/BasaltProject/Deploy/'.self::OLD.'/*' => Http::response($this->egg('Old egg')),
        ]);

        $response = $this->get('/projects/server/egg?version='.self::OLD);

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="egg-basalt-server-a1b2c3d.json"');
        $this->assertSame('Old egg', json_decode($response->getContent(), true)['name']);
    }

    public function test_malformed_sha_is_404(): void
    {
        Http::fake();

        $this->get('/projects/server/egg?version=abc')->assertNotFound();
        $this->get('/projects/server/egg?version[]=x')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_sha_outside_history_is_404_without_fetching_content(): void
    {
        Http::fake([self::API => Http::response($this->commits())]);

        $this->get('/projects/server/egg?version='.str_repeat('0', 40))->assertNotFound();
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'raw.githubusercontent.com'));
    }

    public function test_valid_version_without_content_redirects_to_the_commit(): void
    {
        Http::fake([
            self::API => Http::response($this->commits()),
            self::RAW => Http::response('', 500),
        ]);

        $this->get('/projects/server/egg?version='.self::OLD)
            ->assertRedirect('https://github.com/BasaltProject/Deploy/blob/'.self::OLD.'/pterodactyl/egg-basalt-server.json');
    }

    public function test_history_falls_back_when_github_fails(): void
    {
        Http::fake([self::API => Http::response($this->commits())]);
        $this->assertCount(2, Eggs::history('proxy'));

        Cache::forget('eggs.history.proxy');
        Http::fake([self::API => Http::response('', 500)]);

        $this->assertSame([self::NEW, self::OLD], array_column(Eggs::history('proxy'), 'sha'));
    }
}
