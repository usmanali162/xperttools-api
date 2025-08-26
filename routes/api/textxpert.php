<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TextXpert\TextAnalyzerController;
use App\Http\Controllers\Api\TextXpert\CaseConverterController;

// Text Analyzer (Word/Character Counter)
Route::controller(TextAnalyzerController::class)->group(function () {
    Route::post('/text-analyzer', 'analyze');
});

// Case Converter
Route::controller(CaseConverterController::class)->group(function () {
    Route::post('/case-converter', 'convert');
    Route::get('/case-converter/options', 'getSupportedCases');
});