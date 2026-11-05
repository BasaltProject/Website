<?php

use App\Http\Controllers\SeoController;
use App\Http\Controllers\TimingsController;
use App\Support\Eggs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing')->name('home');

Route::livewire('/projects', 'pages::projects')->name('projects');
Route::livewire('/projects/server', 'pages::server')->name('projects.server');
Route::livewire('/projects/proxy', 'pages::proxy')->name('projects.proxy');
Route::livewire('/projects/{project}/builds', 'pages::builds')->whereIn('project', ['server', 'proxy'])->name('projects.builds');

Route::get('/projects/{project}/egg', function (Request $request, string $project) {
    $sha = $request->query('version');
    $short = null;

    if ($sha !== null) {
        abort_unless(is_string($sha) && Eggs::validSha($sha), 404);

        $known = collect(Eggs::history($project))->contains('sha', $sha);
        abort_unless($known, 404);

        $short = substr($sha, 0, 7);
    }

    $json = Eggs::json($project, $sha);

    if ($json === null) {
        return redirect()->away($sha === null ? Eggs::blobUrl($project) : Eggs::commitBlobUrl($project, $sha));
    }

    return response($json, 200, [
        'Content-Type' => 'application/json',
        'Content-Disposition' => 'attachment; filename="egg-basalt-'.$project.($short ? '-'.$short : '').'.json"',
    ]);
})->whereIn('project', ['server', 'proxy'])->name('projects.egg');

// Profiles uploaded by a running server. The link is printed in a console, so it lives at the root.
Route::livewire('/timings', 'pages::timings-open')->name('timings.open');
Route::livewire('/timings/{key}', 'pages::timings')->whereAlphaNumeric('key')->name('timings.show');
Route::get('/timings/{key}/data', [TimingsController::class, 'data'])->whereAlphaNumeric('key')->name('timings.data');
Route::get('/timings/{key}/download', [TimingsController::class, 'download'])->whereAlphaNumeric('key')->name('timings.download');

Route::livewire('/docs', 'pages::docs')->name('docs');
Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

Route::livewire('/plugins', 'pages::plugins')->name('plugins');

Route::livewire('/donate', 'pages::donate')->name('donate');

// Both are generated, so new documentation pages are listed without touching anything.
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/og/docs/{section}/{slug}.png', [SeoController::class, 'docCover'])->name('og.doc');
Route::get('/og/{name}.png', [SeoController::class, 'cover'])->name('og.cover');
