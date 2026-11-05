<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/** Social preview images: fixed covers for the main pages, and a title drawn over a template for docs. */
class OgImage
{
    public const COVERS = ['home', 'server', 'proxy'];

    private const LEFT = 80;

    private const RIGHT = 1120;

    /** Absolute path of a fixed cover, or null when the name is unknown. */
    public static function cover(string $name): ?string
    {
        return in_array($name, self::COVERS, true) ? resource_path("og/$name.png") : null;
    }

    /**
     * Draws the article title and description over the section template, cached by content so an edit
     * refreshes it. Any failure serves the bare template: a preview without text beats a broken image.
     */
    public static function doc(string $section, string $title, string $description = '', ?string &$failure = null): string
    {
        $template = resource_path("og/docs-$section.png");
        $file = storage_path('framework/cache/og/'.$section.'-'.md5($title."\n".$description).'.png');

        if (is_file($file)) {
            return $file;
        }

        try {
            return self::render($template, $file, $title, $description);
        } catch (Throwable $e) {
            Log::warning('OG image failed: '.$e->getMessage());
            $failure = $e::class.': '.$e->getMessage();

            return $template;
        }
    }

    private static function render(string $template, string $file, string $title, string $description): string
    {
        $image = imagecreatefrompng($template);
        $font = resource_path('og/InstrumentSans-600.ttf');
        $white = imagecolorallocate($image, 246, 247, 249);
        $muted = imagecolorallocate($image, 154, 160, 171);

        // The decorative edges take the top and bottom ~80px, so the text block has to fit between them.
        imagettftext($image, 26, 0, self::RIGHT - self::width('basaltmc.org/docs', $font, 26), 185, $muted, $font, 'basaltmc.org/docs');

        $titleLines = self::wrap($title, $font, 78, 2);
        $y = 388;

        foreach ($titleLines as $line) {
            imagettftext($image, 78, 0, self::LEFT, $y, $white, $font, $line);
            $y += 92;
        }

        // A two line title leaves room for one line of description; a short one gets two.
        $descLines = $description === '' ? [] : self::wrap($description, $font, 28, count($titleLines) > 1 ? 1 : 2);
        $y += -92 + 58;

        foreach ($descLines as $line) {
            imagettftext($image, 28, 0, self::LEFT, $y, $muted, $font, $line);
            $y += 40;
        }

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }

        imagepng($image, $file);

        return $file;
    }

    /** @return list<string> */
    private static function wrap(string $text, string $font, int $size, int $maxLines): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            $try = $current === '' ? $word : "$current $word";

            if ($current !== '' && self::width($try, $font, $size) > self::RIGHT - self::LEFT) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }

        $lines[] = $current;

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], '.,; ').'...';
        }

        return $lines;
    }

    private static function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : $box[2] - $box[0];
    }
}
