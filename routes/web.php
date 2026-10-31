<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing')->name('home');

Route::livewire('/projects', 'pages::projects')->name('projects');
Route::livewire('/projects/server', 'pages::server')->name('projects.server');
Route::livewire('/projects/proxy', 'pages::proxy')->name('projects.proxy');

Route::livewire('/docs', 'pages::docs')->name('docs');
Route::livewire('/docs/{section}/{slug}', 'pages::doc')->name('docs.show');

Route::livewire('/plugins', 'pages::plugins')->name('plugins');

Route::livewire('/donate', 'pages::donate')->name('donate');
