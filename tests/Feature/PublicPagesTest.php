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
}
