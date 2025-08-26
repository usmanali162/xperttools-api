<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtiliXpert\UrlShortenerController;

Route::get('/', function () {
    return view('welcome');
});

// URL Shortener redirect
Route::get('/s/{shortCode}', [UrlShortenerController::class, 'redirect']);
