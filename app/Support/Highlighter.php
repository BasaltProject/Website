<?php

namespace App\Support;

/**
 * A small syntax highlighter for the handful of languages the site shows.
 * Written here rather than pulled in as a dependency: the grammars needed are
 * tiny and the output has to match the site's palette exactly.
 */
class Highlighter
{
    protected const LUA_KEYWORDS = 'and|break|do|elseif|else|end|false|for|function|goto|if|in|local|nil|not|or|repeat|return|then|true|until|while';

    protected const SHELL_KEYWORDS = 'if|then|elif|else|fi|for|while|until|do|done|case|esac|function|return|export|local|set|cd';

    /** Token patterns per language, tried in order. First match wins. */
    protected static function grammar(string $language): array
    {
        return match ($language) {
            'lua' => [
                'comment' => '--\[\[.*?\]\]|--[^\n]*',
                'string' => '\[\[.*?\]\]|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'',
                'number' => '\b0[xX][0-9a-fA-F]+\b|\b\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\b',
                'keyword' => '\b(?:'.self::LUA_KEYWORDS.')\b',
                'function' => '\b[A-Za-z_]\w*(?=\s*\()',
                'property' => '(?<=[.:])[A-Za-z_]\w*',
                'operator' => '\.\.|[=~<>]=|[-+*\/%#^<>=]',
            ],
            'bash', 'sh', 'shell' => [
                'comment' => '#[^\n]*',
                'string' => '"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'',
                'flag' => '(?<=\s)--?[A-Za-z][\w-]*',
                'keyword' => '\b(?:'.self::SHELL_KEYWORDS.')\b',
                'function' => '(?:^|(?<=\n)|(?<=\| ))\s*[A-Za-z_][\w.\/-]*',
                'variable' => '\$\{?\w+\}?',
                'number' => '\b\d+\b',
                'operator' => '[|&;<>=]',
            ],
            'toml', 'ini' => [
                'comment' => '#[^\n]*',
                'section' => '^\s*\[+[^\]\n]+\]+',
                'string' => '"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'',
                'keyword' => '\b(?:true|false)\b',
                'number' => '\b\d+(?:\.\d+)?\b',
                'property' => '^\s*[A-Za-z_][\w.-]*(?=\s*=)',
                'operator' => '=',
            ],
            default => [],
        };
    }

    public static function highlight(string $code, ?string $language): string
    {
        $grammar = self::grammar(strtolower((string) $language));

        if ($grammar === []) {
            return e($code);
        }

        $pattern = '/'.collect($grammar)
            ->map(fn (string $rule, string $name) => "(?<{$name}>{$rule})")
            ->implode('|').'/msu';

        $result = preg_replace_callback($pattern, function (array $matches) use ($grammar) {
            foreach (array_keys($grammar) as $name) {
                if (($matches[$name] ?? '') === '') {
                    continue;
                }

                // Keep surrounding whitespace outside the span so indentation survives.
                $token = $matches[$name];
                $trimmed = ltrim($token);
                $indent = substr($token, 0, strlen($token) - strlen($trimmed));

                return $indent.'<span class="tok-'.$name.'">'.e($trimmed).'</span>';
            }

            return e($matches[0]);
        }, self::protect($code));

        return self::restore($result ?? e($code));
    }

    /** Escape ahead of tokenising so untouched text is safe, then undo inside tokens. */
    protected static function protect(string $code): string
    {
        return strtr($code, ['&' => "\x01amp\x02", '<' => "\x01lt\x02", '>' => "\x01gt\x02"]);
    }

    protected static function restore(string $html): string
    {
        return strtr($html, [
            "\x01amp\x02" => '&amp;',
            "\x01lt\x02" => '&lt;',
            "\x01gt\x02" => '&gt;',
            // e() double-escaped the sentinels it found inside token text.
            '&#039;' => "'",
        ]);
    }
}
