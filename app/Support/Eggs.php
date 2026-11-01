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

    public static function json(string $project): ?string
    {
        $path = self::path($project);

        if ($path === null) {
            return null;
        }

        $fresh = Cache::get("eggs.json.$project");

        if (is_string($fresh)) {
            return $fresh;
        }

        try {
            $response = Http::timeout(3)->get(self::rawUrl($path));
            $body = $response->successful() ? $response->body() : '';
            $decoded = json_decode($body, true);

            if (is_array($decoded) && array_key_exists('meta', $decoded)) {
                Cache::put("eggs.json.$project", $body, 600);
                Cache::put("eggs.json.stale.$project", $body, self::STALE);

                return $body;
            }
        } catch (Throwable) {
            //
        }

        $stale = Cache::get("eggs.json.stale.$project");

        return is_string($stale) ? $stale : null;
    }

    public static function updated(string $project): ?string
    {
        $path = self::path($project);

        if ($path === null) {
            return null;
        }

        $repo = trim((string) parse_url(config('basalt.repos.deploy'), PHP_URL_PATH), '/');

        $date = Cache::remember("eggs.updated.$project", 600, function () use ($repo, $path) {
            try {
                $response = Http::timeout(3)->acceptJson()->get("https://api.github.com/repos/$repo/commits", ['path' => $path, 'per_page' => 1]);
                $date = $response->successful() ? $response->json('0.commit.committer.date') : null;

                return is_string($date) ? $date : '';
            } catch (Throwable) {
                return '';
            }
        });

        return $date !== '' ? $date : null;
    }

    private static function rawUrl(string $path): string
    {
        $repo = trim((string) parse_url(config('basalt.repos.deploy'), PHP_URL_PATH), '/');

        return "https://raw.githubusercontent.com/$repo/main/$path";
    }
}
