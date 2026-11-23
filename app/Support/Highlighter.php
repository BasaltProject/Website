<?php

namespace App\Support;

/**
 * A small syntax highlighter for the handful of languages the site shows.
 * Written here rather than pulled in as a dependency: the grammars needed are
 * tiny and the output has to match the site's palette exactly.
 */
class Highlighter
{
    protected const CPP_KEYWORDS = 'auto|bool|break|case|char|class|const|constexpr|continue|default|delete|do|double|else|enum|false|float|for|if|inline|int|long|namespace|new|nullptr|return|short|signed|sizeof|static|struct|switch|template|true|typedef|typename|union|unsigned|using|void|while';

    protected const RUST_KEYWORDS = 'as|break|const|continue|crate|else|enum|extern|false|fn|for|if|impl|in|let|loop|match|mod|move|mut|pub|ref|return|self|static|struct|trait|true|type|unsafe|use|where|while';

    protected const GO_KEYWORDS = 'break|case|chan|const|continue|default|defer|else|fallthrough|false|for|func|go|goto|if|import|interface|map|nil|package|range|return|select|struct|switch|true|type|var';

    protected const ZIG_KEYWORDS = 'align|and|anyerror|bool|break|catch|comptime|const|continue|defer|else|enum|error|export|extern|false|fn|for|if|inline|noreturn|null|opaque|or|orelse|packed|pub|return|struct|switch|test|true|try|undefined|union|unreachable|usingnamespace|var|void|while|u8|u16|u32|u64|usize|i8|i16|i32|i64|isize|f32|f64';

    protected const TS_KEYWORDS = 'as|async|await|break|case|class|const|continue|declare|default|else|enum|export|extends|false|for|from|function|if|implements|import|in|interface|let|new|null|of|private|public|readonly|return|static|switch|this|throw|true|try|type|undefined|var|void|while';

    protected const KOTLIN_KEYWORDS = 'abstract|as|break|class|companion|const|continue|data|do|else|enum|false|for|fun|if|import|in|interface|is|lateinit|null|object|open|override|package|private|public|return|sealed|super|this|throw|true|try|typealias|val|var|when|while';

    protected const JAVA_KEYWORDS = 'abstract|boolean|break|byte|case|catch|char|class|continue|default|do|double|else|enum|extends|false|final|float|for|if|implements|import|instanceof|int|interface|long|new|null|package|private|protected|public|return|short|static|super|switch|this|throw|throws|true|try|void|while';

    protected const CSHARP_KEYWORDS = 'abstract|as|bool|break|case|catch|char|class|const|continue|default|do|double|else|enum|false|float|for|foreach|if|in|int|interface|internal|is|long|namespace|new|null|override|private|protected|public|readonly|return|sealed|static|string|struct|switch|this|throw|true|try|using|var|virtual|void|while';

    protected const DOCKER_KEYWORDS = 'FROM|RUN|CMD|LABEL|EXPOSE|ENV|ADD|COPY|ENTRYPOINT|VOLUME|USER|WORKDIR|ARG|ONBUILD|STOPSIGNAL|HEALTHCHECK|SHELL|AS';

    protected const SHELL_KEYWORDS = 'if|then|elif|else|fi|for|while|until|do|done|case|esac|function|return|export|local|set|cd';

    /** The shared shape of the C-family grammars: comments, strings, numbers, keywords, calls and members. */
    protected static function clike(string $keywords, array $overrides = []): array
    {
        return array_merge([
            'comment' => '\/\/[^\n]*|\/\*.*?\*\/',
            'string' => '"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'',
            'keyword' => '\b(?:'.$keywords.')\b',
            'number' => '\b0[xX][0-9a-fA-F]+\b|\b\d+(?:\.\d+)?(?:[eE][+-]?\d+)?[fFuUlL]*\b',
            'function' => '\b[A-Za-z_]\w*(?=\s*\()',
            'property' => '(?<=\.|->|::)[A-Za-z_]\w*',
            'operator' => '->|=>|::|[=!<>]=|&&|\|\||[-+*\/%<>=&|^!]',
        ], $overrides);
    }

    /** Token patterns per language, tried in order. First match wins. */
    protected static function grammar(string $language): array
    {
        return match ($language) {
            'cpp', 'c++', 'c', 'h', 'hpp' => self::clike(self::CPP_KEYWORDS, ['keyword' => '^\s*#\s*\w+|\b(?:'.self::CPP_KEYWORDS.')\b']),
            'rust', 'rs' => self::clike(self::RUST_KEYWORDS, ['string' => '"(?:\\\\.|[^"\\\\])*"', 'function' => '\b[A-Za-z_]\w*!?(?=\s*\()']),
            'go', 'golang' => self::clike(self::GO_KEYWORDS, ['string' => '`[^`]*`|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'']),
            'zig' => self::clike(self::ZIG_KEYWORDS, ['function' => '@[A-Za-z_]\w*|\b[A-Za-z_]\w*(?=\s*\()']),
            'ts', 'typescript', 'js', 'javascript' => self::clike(self::TS_KEYWORDS, ['string' => '`(?:\\\\.|[^`\\\\])*`|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'']),
            'kotlin', 'kt' => self::clike(self::KOTLIN_KEYWORDS),
            'java' => self::clike(self::JAVA_KEYWORDS),
            'csharp', 'cs', 'c#' => self::clike(self::CSHARP_KEYWORDS),
            'json' => [
                'property' => '"(?:\\\\.|[^"\\\\])*"(?=\s*:)',
                'string' => '"(?:\\\\.|[^"\\\\])*"',
                'keyword' => '\b(?:true|false|null)\b',
                'number' => '-?\b\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\b',
            ],
            'yaml', 'yml' => [
                'comment' => '#[^\n]*',
                'flag' => '^\s*-(?=\s)',
                'property' => '(?<=^|\s)[A-Za-z_][\w.\/-]*(?=:(?:\s|$))',
                'string' => '"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'',
                'keyword' => '\b(?:true|false|null|yes|no)\b',
                'number' => '\b\d+(?:\.\d+)?\b',
                'variable' => '\$\{\{[^}]*\}\}|\$\{?\w+\}?',
                'operator' => '[:|>-]',
            ],
            'dockerfile', 'docker' => [
                'comment' => '#[^\n]*',
                'string' => '"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'',
                'keyword' => '^\s*(?:'.self::DOCKER_KEYWORDS.')\b',
                'flag' => '(?<=\s)--?[A-Za-z][\w-]*',
                'variable' => '\$\{?\w+\}?',
                'number' => '\b\d+\b',
                'operator' => '[|&;<>=\\\\]',
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
