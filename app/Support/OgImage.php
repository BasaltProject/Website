<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/** Social preview images: fixed covers for the main pages, and a title drawn over a template for docs. */
class OgImage
{
    public const COVERS = ['home', 'server', 'proxy'];

    private const WIDTH_MAX = 1040;

    private const LINES_MAX = 3;

    /** Absolute path of a fixed cover, or null when the name is unknown. */
    public static function cover(string $name): ?string
    {
        return in_array($name, self::COVERS, true) ? resource_path("og/$name.png") : null;
    }

    /**
     * Draws the article title over the section template, cached by content so an edit refreshes it.
     * Any failure serves the bare template: a preview without the title beats a broken image.
     */
    public static function doc(string $section, string $title, ?string &$failure = null): string
    {
        $template = resource_path("og/docs-$section.png");
        $file = storage_path('framework/cache/og/'.$section.'-'.md5($title).'.png');

        if (is_file($file)) {
            return $file;
        }

        try {
            return self::render($template, $file, $title);
        } catch (Throwable $e) {
            Log::warning('OG image failed: '.$e->getMessage());
            $failure = $e::class.': '.$e->getMessage();

            return $template;
        }
    }

    private static function render(string $template, string $file, string $title): string
    {
        $image = imagecreatefrompng($template);
        $font = resource_path('og/InstrumentSans-600.ttf');
        $color = imagecolorallocate($image, 245, 246, 248);
        $size = 58;

        foreach (self::wrap($title, $font, $size) as $i => $line) {
            imagettftext($image, $size, 0, 80, 370 + $i * 76, $color, $font, $line);
        }

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }

        imagepng($image, $file);

        return $file;
    }

    /** @return list<string> */
    private static function wrap(string $text, string $font, int $size): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            $try = $current === '' ? $word : "$current $word";

            if ($current !== '' && self::width($try, $font, $size) > self::WIDTH_MAX) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }

        $lines[] = $current;

        if (count($lines) > self::LINES_MAX) {
            $lines = array_slice($lines, 0, self::LINES_MAX);
            $lines[self::LINES_MAX - 1] = rtrim($lines[self::LINES_MAX - 1], '.,; ').'...';
        }

        return $lines;
    }

    private static function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : $box[2] - $box[0];
    }
}
