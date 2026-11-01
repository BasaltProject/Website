<?php

use App\Support\Eggs;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing')->name('home');

Route::livewire('/projects', 'pages::projects')->name('projects');
Route::livewire('/projects/server', 'pages::server')->name('projects.server');
Route::livewire('/projects/proxy', 'pages::proxy')->name('projects.proxy');
Route::livewire('/projects/{project}/builds', 'pages::builds')->whereIn('project', ['server', 'proxy'])->name('projects.builds');

Route::get('/projects/{project}/egg', function (string $project) {
    $json = Eggs::json($project);

    if ($json === null) {
        return redirect()->away(Eggs::blobUrl($project));
    }

    return response($json, 200, [
        'Content-Type' => 'application/json',
        'Content-Disposition' => 'attachment; filename="egg-basalt-'.$project.'.json"',
    ]);
})->whereIn('project', ['server', 'proxy'])->name('projects.egg');

Route::livewire('/docs', 'pages::docs')->name('docs');
Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

Route::livewire('/plugins', 'pages::plugins')->name('plugins');

Route::livewire('/donate', 'pages::donate')->name('donate');
