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
            'landing' => ['/'],
            'health' => ['/up'],
            'home' => ['/dev'],
            'projects' => ['/dev/projects'],
            'server' => ['/dev/projects/server'],
            'proxy' => ['/dev/projects/proxy'],
            'docs' => ['/dev/docs'],
            'server documentation' => ['/dev/docs/server/introduction'],
            'proxy documentation' => ['/dev/docs/proxy/introduction'],
            'donations' => ['/dev/donate'],
            'plugins' => ['/dev/plugins'],
        ];
    }
}
