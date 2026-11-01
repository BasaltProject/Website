<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function report(): array
    {
        return [
            'format' => 'basalt-timings',
            'version' => 2,
            'basalt' => '26.3',
            'generated' => '2026-11-01_12-00-00',
            'uptime_seconds' => 4000,
            'measured_seconds' => 60,
            'platform' => ['os' => 'Linux', 'arch' => 'x64', 'cores' => 8],
            'server' => [
                'players_online' => 3, 'players_max' => 20, 'worlds' => 1, 'chunks_loaded' => 400,
                'cpu_percent_of_one_core' => 35.5, 'process_resident_bytes' => 800000000,
                'worlds_detail' => [['name' => 'world', 'chunks' => 400, 'entities' => 90, 'players' => 3]],
            ],
            'ticks' => [
                'per_second' => ['5s' => 20.0, '1m' => 19.5, '5m' => 19.9],
                'duration_millis' => ['1m' => ['mean' => 12.5, 'p95' => 20.1, 'max' => 70.2]],
                'timeline' => ['seconds' => 2, 'points' => [
                    ['ticks' => 20, 'mean_millis' => 10.0, 'max_millis' => 22.0],
                    ['ticks' => 20, 'mean_millis' => 14.0, 'max_millis' => 70.0],
                ]],
            ],
            'sections' => [['name' => 'tick', 'calls' => 1200, 'total_millis' => 9000.0, 'average_millis' => 7.5, 'max_millis' => 70.2]],
            'threads' => [[
                'name' => 'Server thread',
                'total_millis' => 9000.0,
                'root' => ['name' => 'root', 'calls' => 1200, 'total_millis' => 9000.0, 'self_millis' => 0, 'max_millis' => 0, 'children' => [
                    ['name' => 'tick', 'calls' => 1200, 'total_millis' => 9000.0, 'self_millis' => 1000.0, 'max_millis' => 70.2, 'children' => [
                        ['name' => 'entity.zombie', 'calls' => 5000, 'total_millis' => 8000.0, 'self_millis' => 8000.0, 'max_millis' => 3.0, 'children' => []],
                    ]],
                ]],
            ]],
        ];
    }

    public function test_a_server_can_upload_a_report_and_read_it_back(): void
    {
        $body = json_encode($this->report());

        $response = $this->call('PUT', '/api/timings', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);

        $response->assertCreated()->assertJsonStructure(['key', 'url']);
        $key = $response->json('key');
        $this->assertSame(rtrim(config('app.url'), '/')."/timings/{$key}", $response->json('url'));

        $this->get("/timings/{$key}")
            ->assertOk()
            ->assertSee('entity.zombie')
            ->assertSee('Server thread')
            ->assertSee('Download JSON');

        $download = $this->get("/timings/{$key}/download");
        $download->assertOk()->assertHeader('Content-Type', 'application/json');
        $this->assertSame($body, $download->getContent());
    }

    public function test_it_refuses_anything_that_is_not_a_report(): void
    {
        $this->call('PUT', '/api/timings', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"hello":"world"}')
            ->assertStatus(422);
        $this->call('PUT', '/api/timings', [], [], [], ['CONTENT_TYPE' => 'application/json'], '')
            ->assertStatus(422);
        $this->call('PUT', '/api/timings', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json')
            ->assertStatus(422);
    }

    public function test_an_unknown_or_malformed_key_is_a_404(): void
    {
        $this->get('/timings/abcdefghij')->assertNotFound();
        $this->get('/timings/short')->assertNotFound();
        $this->get('/timings/abcdefghij/download')->assertNotFound();
    }

    public function test_an_older_report_without_a_call_tree_still_renders(): void
    {
        $old = ['format' => 'basalt-timings', 'version' => 1, 'basalt' => '26.2', 'sections' => []];

        $key = $this->call('PUT', '/api/timings', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($old))
            ->json('key');

        $this->get("/timings/{$key}")->assertOk();
    }
}
