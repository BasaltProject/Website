<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Documentation read off disk from the `documentation` submodule. Pages are
 * MDX files at <section>/<order>-<slug>.mdx with a front matter head.
 */
class Docs
{
    /** Sections in navigation order. The key is the directory name. */
    public const SECTIONS = [
        'server' => ['title' => 'Basalt Server', 'summary' => 'Running, configuring and extending the server.'],
        'proxy' => ['title' => 'Basalt Proxy', 'summary' => 'Running, configuring and extending the proxy.'],
    ];

    public static function path(): string
    {
        return config('basalt.docs.path');
    }

    /** Every page, in section then file order. */
    public static function all(): Collection
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $pages = collect();

        foreach (array_keys(self::SECTIONS) as $section) {
            $files = glob(self::path().'/'.$section.'/*.mdx') ?: [];
            sort($files);

            foreach ($files as $file) {
                $pages->push(self::parse($section, $file));
            }
        }

        return $cache = $pages;
    }

    public static function tree(): Collection
    {
        return self::all()
            ->groupBy('section')
            ->map(fn (Collection $pages, string $section) => [
                'key' => $section,
                'title' => self::SECTIONS[$section]['title'],
                'summary' => self::SECTIONS[$section]['summary'],
                'pages' => $pages->values(),
            ])
            ->values();
    }

    /** Pages of one section in reading order. */
    public static function section(string $section): Collection
    {
        return self::all()->where('section', $section)->values();
    }

    /** The first page of a section, where its tab in the sidebar leads. */
    public static function first(string $section): ?array
    {
        return self::section($section)->first();
    }

    /**
     * A section's pages split into runs that share a group, in file order.
     *
     * @return Collection<int, array{title: string, pages: Collection}>
     */
    public static function groups(string $section): Collection
    {
        $groups = collect();

        foreach (self::section($section) as $page) {
            $last = $groups->count() - 1;

            if ($last >= 0 && $groups[$last]['title'] === $page['group']) {
                $groups[$last]['pages']->push($page);
            } else {
                $groups->push(['title' => $page['group'], 'pages' => collect([$page])]);
            }
        }

        return $groups;
    }

    public static function find(string $section, string $slug): ?array
    {
        return self::all()->first(
            fn (array $page) => $page['section'] === $section && $page['slug'] === $slug
        );
    }

    /** Title, description and body search. Ranked so title hits beat body hits. */
    public static function search(string $term): Collection
    {
        $term = trim($term);

        if (Str::length($term) < 2) {
            return collect();
        }

        return self::all()
            ->map(function (array $page) use ($term) {
                $score = 0;

                if (Str::contains($page['title'], $term, ignoreCase: true)) {
                    $score += 10;
                }

                if (Str::contains($page['description'], $term, ignoreCase: true)) {
                    $score += 5;
                }

                if (Str::contains($page['text'], $term, ignoreCase: true)) {
                    $score += 1;
                }

                return $score > 0
                    ? [...$page, 'score' => $score, 'excerpt' => self::excerpt($page['text'], $term)]
                    : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /** A window of plain text around the first match, for the results list. */
    protected static function excerpt(string $text, string $term, int $radius = 90): string
    {
        $position = stripos($text, $term);

        if ($position === false) {
            return Str::limit($text, $radius * 2);
        }

        $start = max(0, $position - $radius);
        $excerpt = substr($text, $start, $radius * 2 + Str::length($term));

        return ($start > 0 ? '...' : '').trim($excerpt).'...';
    }

    protected static function parse(string $section, string $file): array
    {
        $raw = (string) file_get_contents($file);
        [$matter, $body] = self::splitFrontMatter($raw);

        $name = pathinfo($file, PATHINFO_FILENAME);
        $slug = (string) preg_replace('/^\d+[-_]/', '', $name);

        return [
            'section' => $section,
            'slug' => $slug,
            'title' => $matter['title'] ?? Str::headline($slug),
            'description' => $matter['description'] ?? '',
            'group' => $matter['group'] ?? '',
            'source' => $body,
            'text' => Mdx::toText($body),
            'url' => route('docs.show', ['section' => $section, 'slug' => $slug]),
            'edit_url' => config('basalt.docs.edit_base')."/{$section}/{$name}.mdx",
        ];
    }

    /** @return array{0: array<string, string>, 1: string} */
    protected static function splitFrontMatter(string $raw): array
    {
        if (! preg_match('/^---\R(.*?)\R---\R?(.*)$/s', $raw, $matches)) {
            return [[], $raw];
        }

        $matter = [];

        foreach (preg_split('/\R/', $matches[1]) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $matter[trim($key)] = trim(trim($value), "\"'");
        }

        return [$matter, $matches[2]];
    }
}
