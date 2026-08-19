<?php

use Illuminate\Support\Facades\Route;

// The public root stays on the coming soon landing until the project is announced.
Route::get('/', function () {
    return view('welcome');
});

// The site under construction. Everything real lives here for now.
Route::prefix('dev')->name('dev.')->group(function () {
    Route::livewire('/', 'pages::landing')->name('home');

    Route::livewire('/projects', 'pages::projects')->name('projects');
    Route::livewire('/projects/server', 'pages::server')->name('projects.server');
    Route::livewire('/projects/proxy', 'pages::proxy')->name('projects.proxy');

    Route::livewire('/docs', 'pages::docs')->name('docs');
    Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

    Route::livewire('/plugins', 'pages::plugins')->name('plugins');

    Route::livewire('/donate', 'pages::donate')->name('donate');
});
