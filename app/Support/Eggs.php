<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pterodactyl eggs published in the deploy repository. A fresh copy is cached
 * briefly; the last valid one is kept for long, so GitHub going down never
 * breaks the download.
 */
class Eggs
{
    private const STALE = 60 * 60 * 24 * 30;

    public static function path(string $project): ?string
    {
        return config("basalt.eggs.$project");
    }

    /** Where the egg lives on GitHub, for when no copy can be served. */
    public static function blobUrl(string $project): string
    {
        return config('basalt.repos.deploy').'/blob/main/'.self::path($project);
    }

    public static function json(string $project, ?string $sha = null): ?string
    {
        $path = self::path($project);

        if ($path === null) {
            return null;
        }

        if ($sha !== null) {
            return self::pinned($path, $project, $sha);
        }

        $fresh = Cache::get("eggs.json.$project");

        if (is_string($fresh)) {
            return $fresh;
        }

        $body = self::fetch(self::rawUrl($path, 'main'));

        if ($body !== null) {
            Cache::put("eggs.json.$project", $body, 600);
            Cache::put("eggs.json.stale.$project", $body, self::STALE);

            return $body;
        }

        $stale = Cache::get("eggs.json.stale.$project");

        return is_string($stale) ? $stale : null;
    }

    /** Commits that touched the egg, newest first. */
    public static function history(string $project): array
    {
        $path = self::path($project);

        if ($path === null) {
            return [];
        }

        $history = GitHub::remember("eggs.history.$project", function () use ($path) {
            $response = GitHub::api()->get('https://api.github.com/repos/'.self::repo().'/commits', ['path' => $path, 'per_page' => 30]);
            $items = $response->successful() ? $response->json() : null;

            if (! is_array($items)) {
                return null;
            }

            $history = [];

            foreach ($items as $item) {
                $sha = $item['sha'] ?? null;

                if (! is_string($sha) || ! self::validSha($sha)) {
                    continue;
                }

                $history[] = [
                    'sha' => $sha,
                    'short' => substr($sha, 0, 7),
                    'date' => (string) ($item['commit']['committer']['date'] ?? ''),
                    'message' => trim(strtok((string) ($item['commit']['message'] ?? ''), "\n") ?: ''),
                    'url' => (string) ($item['html_url'] ?? ''),
                ];
            }

            return $history !== [] ? $history : null;
        });

        return is_array($history) ? $history : [];
    }

    public static function updated(string $project): ?string
    {
        $date = self::history($project)[0]['date'] ?? '';

        return $date !== '' ? $date : null;
    }

    public static function validSha(string $sha): bool
    {
        return preg_match('/\A[0-9a-f]{40}\z/', $sha) === 1;
    }

    /** A commit's content never changes, so it is cached for long. */
    private static function pinned(string $path, string $project, string $sha): ?string
    {
        if (! self::validSha($sha)) {
            return null;
        }

        $key = "eggs.json.$project.$sha";
        $cached = Cache::get($key);

        if (is_string($cached)) {
            return $cached;
        }

        $body = self::fetch(self::rawUrl($path, $sha));

        if ($body !== null) {
            Cache::put($key, $body, self::STALE);
        }

        return $body;
    }

    /** Body of the URL when it is a JSON egg, null otherwise. */
    private static function fetch(string $url): ?string
    {
        try {
            $response = Http::timeout(3)->get($url);
            $body = $response->successful() ? $response->body() : '';
            $decoded = json_decode($body, true);

            if (is_array($decoded) && array_key_exists('meta', $decoded)) {
                return $body;
            }
        } catch (Throwable) {
            //
        }

        return null;
    }

    private static function repo(): string
    {
        return trim((string) parse_url(config('basalt.repos.deploy'), PHP_URL_PATH), '/');
    }

    /** Link to the egg file at a given commit on GitHub. */
    public static function commitBlobUrl(string $project, string $sha): string
    {
        return config('basalt.repos.deploy').'/blob/'.$sha.'/'.self::path($project);
    }

    private static function rawUrl(string $path, string $ref): string
    {
        return 'https://raw.githubusercontent.com/'.self::repo().'/'.$ref.'/'.$path;
    }
}
