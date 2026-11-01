<?php

use App\Http\Controllers\TimingsController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing')->name('home');

Route::livewire('/projects', 'pages::projects')->name('projects');
Route::livewire('/projects/server', 'pages::server')->name('projects.server');
Route::livewire('/projects/proxy', 'pages::proxy')->name('projects.proxy');

// Profiles uploaded by a running server. The link is printed in a console, so it lives at the root.
Route::livewire('/timings', 'pages::timings-open')->name('timings.open');
Route::livewire('/timings/{key}', 'pages::timings')->whereAlphaNumeric('key')->name('timings.show');
Route::get('/timings/{key}/data', [TimingsController::class, 'data'])->whereAlphaNumeric('key')->name('timings.data');
Route::get('/timings/{key}/download', [TimingsController::class, 'download'])->whereAlphaNumeric('key')->name('timings.download');

Route::livewire('/docs', 'pages::docs')->name('docs');
Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

Route::livewire('/plugins', 'pages::plugins')->name('plugins');

Route::livewire('/donate', 'pages::donate')->name('donate');
