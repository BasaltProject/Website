<?php

namespace App\Http\Controllers;

use App\Support\Docs;
use App\Support\OgImage;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeoController extends Controller
{
    /** Public pages with no per-page data. Timings reports and downloads are deliberately absent. */
    private const STATIC_ROUTES = [
        'home' => 1.0,
        'projects' => 0.8,
        'projects.server' => 0.9,
        'projects.proxy' => 0.9,
        'docs' => 0.8,
        'plugins' => 0.3,
        'donate' => 0.4,
    ];

    public function sitemap(): Response
    {
        $origin = rtrim(config('basalt.url'), '/');
        $entries = [];

        foreach (self::STATIC_ROUTES as $name => $priority) {
            $entries[] = ['loc' => $origin.route($name, absolute: false), 'priority' => $priority];
        }

        foreach (['server', 'proxy'] as $project) {
            $entries[] = ['loc' => $origin.route('projects.builds', $project, absolute: false), 'priority' => 0.5];
        }

        foreach (Docs::all() as $page) {
            $entries[] = [
                'loc' => $origin.route('docs.show', [$page['section'], $page['slug']], absolute: false),
                'lastmod' => Carbon::createFromTimestamp($page['modified'])->toAtomString(),
                'priority' => 0.7,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <url><loc>'.e($entry['loc']).'</loc>';

            if (isset($entry['lastmod'])) {
                $xml .= '<lastmod>'.$entry['lastmod'].'</lastmod>';
            }

            $xml .= '<priority>'.number_format($entry['priority'], 1).'</priority></url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $origin = rtrim(config('basalt.url'), '/');

        $body = "User-agent: *\nDisallow: /timings/\nDisallow: /projects/*/egg\n\nSitemap: {$origin}/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function cover(string $name): BinaryFileResponse
    {
        $path = OgImage::cover($name);
        abort_if($path === null, 404);

        return $this->image($path);
    }

    public function docCover(string $section, string $slug): BinaryFileResponse
    {
        $page = Docs::find($section, $slug);
        abort_if($page === null, 404);

        return $this->image(OgImage::doc($section, $page['title']));
    }

    private function image(string $path): BinaryFileResponse
    {
        return response()->file($path, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }
}
