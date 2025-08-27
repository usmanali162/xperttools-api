<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TextXpert\TextAnalyzerController;
use App\Http\Controllers\Api\TextXpert\CaseConverterController;
use App\Http\Controllers\Api\TextXpert\LoremIpsumController;

// Text Analyzer (Word/Character Counter)
Route::controller(TextAnalyzerController::class)->group(function () {
    Route::post('/text-analyzer', 'analyze');
});

// Case Converter
Route::controller(CaseConverterController::class)->group(function () {
    Route::post('/case-converter', 'convert');
    Route::get('/case-converter/options', 'getSupportedCases');
});

// Lorem Ipsum Generator
Route::controller(LoremIpsumController::class)->group(function () {
    Route::post('/lorem-generator', 'generate');
    Route::get('/lorem-generator/options', 'getOptions');
});