<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Download links read from the latest GitHub release of a repository. Asset names
 * carry the version, so a fixed "latest/download" link cannot point at them.
 */
class Releases
{
    /** @return array{version: string, assets: array<string, string>}|null */
    public static function latest(string $repo): ?array
    {
        $path = trim((string) parse_url($repo, PHP_URL_PATH), '/');

        $release = Cache::remember("releases.latest.$path", 600, function () use ($path) {
            try {
                $response = Http::timeout(3)->acceptJson()->get("https://api.github.com/repos/$path/releases/latest");

                return $response->successful() ? $response->json() : [];
            } catch (Throwable) {
                return [];
            }
        });

        if (empty($release['assets'])) {
            return null;
        }

        $assets = [];

        foreach ($release['assets'] as $asset) {
            foreach (config('basalt.platforms') as $platform) {
                if (str_contains($asset['name'], '-'.$platform['target'])) {
                    $assets[$platform['target']] = $asset['browser_download_url'];
                }
            }
        }

        return ['version' => ltrim((string) ($release['tag_name'] ?? ''), 'v'), 'assets' => $assets];
    }
}
