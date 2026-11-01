<?php

namespace App\Support;

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

        $release = GitHub::remember("releases.latest.$path", function () use ($path) {
            $response = GitHub::api()->get("https://api.github.com/repos/$path/releases/latest");
            $json = $response->successful() ? $response->json() : null;

            return is_array($json) && $json !== [] ? $json : null;
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

    /**
     * Every stable release, newest first. Nightly, prereleases and drafts are left out.
     *
     * @return list<array{version: string, tag: string, date: ?string, url: ?string, checksums: ?string, assets: list<array{label: string, arch: string, icon: string, url: string, size: int}>}>
     */
    public static function history(string $repo): array
    {
        $path = trim((string) parse_url($repo, PHP_URL_PATH), '/');

        $releases = GitHub::remember("releases.history.$path", function () use ($path) {
            $response = GitHub::api()->get("https://api.github.com/repos/$path/releases?per_page=100");
            $json = $response->successful() ? $response->json() : null;

            return is_array($json) && $json !== [] ? $json : null;
        });

        if (! is_array($releases)) {
            return [];
        }

        $history = [];

        foreach ($releases as $release) {
            $tag = (string) ($release['tag_name'] ?? '');

            if ($tag === '' || strtolower($tag) === 'nightly' || ! empty($release['prerelease']) || ! empty($release['draft'])) {
                continue;
            }

            $assets = [];
            $checksums = null;

            foreach ($release['assets'] ?? [] as $asset) {
                $name = (string) ($asset['name'] ?? '');

                if ($name === 'SHA256SUMS') {
                    $checksums = $asset['browser_download_url'] ?? null;

                    continue;
                }

                foreach (config('basalt.platforms') as $platform) {
                    if (str_contains($name, '-'.$platform['target']) && ! isset($assets[$platform['target']])) {
                        $assets[$platform['target']] = [
                            'label' => $platform['label'],
                            'arch' => $platform['arch'],
                            'icon' => $platform['icon'],
                            'url' => $asset['browser_download_url'],
                            'size' => (int) ($asset['size'] ?? 0),
                        ];
                    }
                }
            }

            $history[] = [
                'version' => ltrim($tag, 'v'),
                'tag' => $tag,
                'date' => $release['published_at'] ?? null,
                'url' => $release['html_url'] ?? null,
                'checksums' => $checksums,
                'assets' => array_values($assets),
            ];
        }

        usort($history, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $history;
    }
}
