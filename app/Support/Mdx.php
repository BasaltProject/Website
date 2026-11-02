<?php

namespace App\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

/**
 * Markdown that can embed Blade components, since there is no PHP runtime for
 * real MDX. The order is load-bearing: fenced code is lifted out so Blade never
 * evaluates it, then Blade, then markdown, then the code comes back highlighted.
 */
class Mdx
{
    public static function render(string $source): string
    {
        [$source, $blocks] = self::liftCodeBlocks($source);

        $html = Str::markdown(
            Blade::render($source, [], deleteCachedView: true),
            // First-party content only: components must survive as raw HTML.
            ['html_input' => 'allow', 'allow_unsafe_links' => false],
        );

        // Internal links navigate through Livewire; done before the code comes back so blocks stay untouched.
        $html = preg_replace('/<a href="\/(?!\/)/', '<a wire:navigate href="/', $html);

        $html = self::restoreCodeBlocks($html, $blocks);

        // Wide tables scroll inside their own box instead of widening the page.
        return str_replace(['<table>', '</table>'], ['<div class="table-wrap"><table>', '</table></div>'], $html);
    }

    /**
     * Rendered page with an id and a hover link on every h2 and h3, plus the
     * list of those headings for the "On this page" index.
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>}
     */
    public static function renderWithHeadings(string $source): array
    {
        $headings = [];
        $used = [];

        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $m) use (&$headings, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5));
            $base = Str::slug($text) ?: 'section';
            $id = $base;

            for ($n = 2; isset($used[$id]); $n++) {
                $id = $base.'-'.$n;
            }

            $used[$id] = true;
            $headings[] = ['id' => $id, 'text' => $text, 'level' => (int) $m[1]];

            return '<h'.$m[1].' id="'.$id.'">'.$m[2]
                .'<a class="heading-anchor" href="#'.$id.'" aria-label="Link to '.e($text).'">#</a></h'.$m[1].'>';
        }, self::render($source));

        return ['html' => $html ?? '', 'headings' => $headings];
    }

    /** Plain text of a page, for search indexing and excerpts. */
    public static function toText(string $source): string
    {
        [$source] = self::liftCodeBlocks($source);

        return trim(html_entity_decode(strip_tags(Str::markdown($source))));
    }

    /** @return array{0: string, 1: array<string, string>} */
    protected static function liftCodeBlocks(string $source): array
    {
        $blocks = [];

        $source = preg_replace_callback(
            '/^```([\w-]*)\n(.*?)^```/ms',
            function (array $matches) use (&$blocks) {
                $key = 'MDXCODE'.count($blocks).'ENDMDXCODE';
                $blocks[$key] = self::codeBlock($matches[2], $matches[1] ?: null);

                return $key;
            },
            $source,
        ) ?? $source;

        return [$source, $blocks];
    }

    protected static function restoreCodeBlocks(string $html, array $blocks): string
    {
        foreach ($blocks as $key => $block) {
            // Markdown wrapped the lone placeholder in a paragraph; drop that.
            $html = str_replace(['<p>'.$key.'</p>', $key], $block, $html);
        }

        return $html;
    }

    public static function codeBlock(string $code, ?string $language, ?string $file = null): string
    {
        $code = rtrim($code, "\n");

        // A file name says more than the language, so it wins the corner when given.
        $caption = $file ?: $language;
        $label = $caption
            ? '<span class="code-lang'.($file ? ' code-file' : '').'">'.e($caption).'</span>'
            : '';

        return '<div class="code-block">'.$label
            .'<pre><code>'.Highlighter::highlight($code, $language).'</code></pre></div>';
    }
}
