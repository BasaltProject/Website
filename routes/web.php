<?php

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

Route::livewire('/docs', 'pages::docs')->name('docs');
Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

Route::livewire('/plugins', 'pages::plugins')->name('plugins');

Route::livewire('/donate', 'pages::donate')->name('donate');
