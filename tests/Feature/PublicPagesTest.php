<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    #[DataProvider('publicPaths')]
    public function test_public_pages_render_after_dependency_updates(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public static function publicPaths(): array
    {
        return [
            'health' => ['/up'],
            'home' => ['/'],
            'projects' => ['/projects'],
            'server' => ['/projects/server'],
            'proxy' => ['/projects/proxy'],
            'docs' => ['/docs'],
            'server documentation' => ['/docs/server/introduction'],
            'proxy documentation' => ['/docs/proxy/introduction'],
            'donations' => ['/donate'],
            'plugins' => ['/plugins'],
        ];
    }

    public function test_sitemap_lists_pages_and_documentation(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('https://basaltmc.org/docs/server/introduction', false);
        $response->assertDontSee('/timings', false);
    }

    public function test_robots_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://basaltmc.org/sitemap.xml');
    }

    public function test_pages_carry_a_canonical_link(): void
    {
        $this->get('/projects')->assertSee('<link rel="canonical" href="https://basaltmc.org/projects">', false);
    }

    public function test_social_images_are_served_as_png(): void
    {
        foreach (['/og/home.png', '/og/server.png', '/og/proxy.png', '/og/docs/server/introduction.png'] as $path) {
            $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/png');
        }

        $this->get('/og/unknown.png')->assertNotFound();
        $this->get('/og/docs/server/missing-page.png')->assertNotFound();
    }

    public function test_structured_data_is_valid_json_ld(): void
    {
        foreach (['/', '/projects/server', '/docs/server/introduction'] as $path) {
            preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $this->get($path)->getContent(), $match);

            $data = json_decode($match[1] ?? '', true);

            $this->assertSame('https://schema.org', $data['@context'] ?? null, $path);
            $this->assertNotEmpty($data['@graph'] ?? [], $path);
        }
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_plugins_page_is_noindex_and_left_out_of_the_sitemap(): void
    {
        $this->get('/plugins')->assertSee('noindex', false);
        $this->get('/sitemap.xml')->assertDontSee('https://basaltmc.org/plugins</loc>', false);
    }
}
