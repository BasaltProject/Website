@props(['lang' => null, 'file' => null])

{{-- Static snippets on marketing pages, highlighted by the same engine as the docs. --}}
{!! \App\Support\Mdx::codeBlock(trim($slot, "\n"), $lang, $file) !!}
