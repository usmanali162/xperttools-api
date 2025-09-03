<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CodeXpert\JsonFormatterController;
use App\Http\Controllers\Api\CodeXpert\XmlBeautifierController;
use App\Http\Controllers\Api\CodeXpert\Base64Controller;

// JSON Formatter & Validator
Route::controller(JsonFormatterController::class)->group(function () {
    Route::post('/json-formatter', 'format');
    Route::get('/json-formatter/options', 'getOptions');
});

// XML Beautifier & Validator
Route::controller(XmlBeautifierController::class)->group(function () {
    Route::post('/xml-beautifier', 'beautify');
    Route::get('/xml-beautifier/options', 'getOptions');
});

// Base64 Encoder/Decoder
Route::controller(Base64Controller::class)->group(function () {
    Route::post('/base64', 'process');
    Route::get('/base64/options', 'getOptions');
});