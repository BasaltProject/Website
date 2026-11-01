<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Profiles uploaded by a running Basalt Server, kept as gzipped JSON on the
 * local disk. A report is addressed by a random key and nothing else: there is
 * no account, and no listing.
 */
class TimingReports
{
    public const FORMAT = 'basalt-timings';

    public const MAX_BYTES = 8 * 1024 * 1024;

    public const RETENTION_DAYS = 30;

    private const KEY_LENGTH = 10;

    private const DIRECTORY = 'timings';

    public static function validKey(string $key): bool
    {
        return preg_match('/^[A-Za-z0-9]{'.self::KEY_LENGTH.'}$/', $key) === 1;
    }

    /**
     * Whether a body is a Basalt timings report worth keeping. Returns the
     * reason it is not, or null when it is one.
     */
    public static function rejection(string $body): ?string
    {
        if ($body === '' || strlen($body) > self::MAX_BYTES) {
            return 'The report is empty or larger than '.(self::MAX_BYTES / 1024 / 1024).' MB.';
        }

        $data = json_decode($body, true, 64);

        if (! is_array($data) || ($data['format'] ?? null) !== self::FORMAT) {
            return 'This is not a Basalt timings report.';
        }

        if (! is_int($data['version'] ?? null) || $data['version'] < 1 || $data['version'] > 2) {
            return 'This report version is not supported.';
        }

        return null;
    }

    public static function store(string $body): string
    {
        self::prune();

        do {
            $key = Str::random(self::KEY_LENGTH);
        } while (Storage::exists(self::path($key)));

        Storage::put(self::path($key), gzencode($body, 6));

        return $key;
    }

    /** The JSON exactly as the server sent it. */
    public static function raw(string $key): ?string
    {
        if (! self::validKey($key) || ! Storage::exists(self::path($key))) {
            return null;
        }

        $body = gzdecode(Storage::get(self::path($key)));

        return $body === false ? null : $body;
    }

    public static function find(string $key): ?array
    {
        $raw = self::raw($key);

        return $raw === null ? null : json_decode($raw, true, 64);
    }

    /** Deletes what is past retention. Cheap enough to run on a fraction of uploads. */
    public static function prune(): int
    {
        if (random_int(1, 20) !== 1) {
            return 0;
        }

        $cutoff = now()->subDays(self::RETENTION_DAYS)->getTimestamp();
        $removed = 0;

        foreach (Storage::files(self::DIRECTORY) as $file) {
            if (Storage::lastModified($file) < $cutoff) {
                Storage::delete($file);
                $removed++;
            }
        }

        return $removed;
    }

    private static function path(string $key): string
    {
        return self::DIRECTORY.'/'.$key.'.json.gz';
    }
}
