<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Shared client for api.github.com. The token is optional: without it GitHub
 * allows only 60 requests per hour per IP.
 */
class GitHub
{
    public const FRESH = 600;

    public const STALE = 60 * 60 * 24 * 30;

    public const COOLDOWN = 120;

    public static function api(): PendingRequest
    {
        $request = Http::timeout(3)->acceptJson();
        $token = config('services.github.token');

        return is_string($token) && $token !== '' ? $request->withToken($token) : $request;
    }

    /** True while a recent failure asks us not to hit GitHub again. */
    public static function cooling(string $key): bool
    {
        return Cache::has("github.cooldown.$key");
    }

    public static function cool(string $key): void
    {
        Cache::put("github.cooldown.$key", true, self::COOLDOWN);
    }

    /**
     * Fresh copy, else a request to GitHub (unless cooling down), else the
     * long-lived backup. Only valid data is ever stored.
     *
     * @param  callable(): mixed  $fetch  returns the data, or null on failure
     */
    public static function remember(string $key, callable $fetch, mixed $empty = []): mixed
    {
        $fresh = Cache::get("$key");

        if ($fresh !== null) {
            return $fresh;
        }

        if (! self::cooling($key)) {
            try {
                $data = $fetch();
            } catch (\Throwable) {
                $data = null;
            }

            if ($data !== null) {
                Cache::put($key, $data, self::FRESH);
                Cache::put("$key.stale", $data, self::STALE);

                return $data;
            }

            self::cool($key);
        }

        return Cache::get("$key.stale", $empty);
    }
}
