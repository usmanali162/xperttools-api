<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TextXpert\TextAnalyzerController;

// Text Analyzer (Word/Character Counter)
Route::controller(TextAnalyzerController::class)->group(function () {
    Route::post('/text-analyzer', 'analyze');
});