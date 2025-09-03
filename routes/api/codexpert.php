<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CodeXpert\JsonFormatterController;

// JSON Formatter & Validator
Route::controller(JsonFormatterController::class)->group(function () {
    Route::post('/json-formatter', 'format');
    Route::get('/json-formatter/options', 'getOptions');
});