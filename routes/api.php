<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Core routes (auth, user management, etc.)
    Route::prefix('auth')->group(function () {
        // Authentication routes will go here
    });
    
    // Tool categories - corrected syntax
    Route::prefix('utilixpert')->group(function () {
        require __DIR__ . '/api/utilixpert.php';
    });
    
    Route::prefix('textxpert')->group(function () {
        require __DIR__ . '/api/textxpert.php';
    });
    
    Route::prefix('codexpert')->group(function () {
        require __DIR__ . '/api/codexpert.php';
    });
    
    Route::prefix('filexpert')->group(function () {
        require __DIR__ . '/api/filexpert.php';
    });
    
    Route::prefix('designxpert')->group(function () {
        require __DIR__ . '/api/designxpert.php';
    });
    
    Route::prefix('securexpert')->group(function () {
        require __DIR__ . '/api/securexpert.php';
    });
    
    Route::prefix('bizxpert')->group(function () {
        require __DIR__ . '/api/bizxpert.php';
    });
    
    Route::prefix('mediaxpert')->group(function () {
        require __DIR__ . '/api/mediaxpert.php';
    });
    
    Route::prefix('timexpert')->group(function () {
        require __DIR__ . '/api/timexpert.php';
    });
    
    Route::prefix('seoxpert')->group(function () {
        require __DIR__ . '/api/seoxpert.php';
    });
    
    Route::prefix('testxpert')->group(function () {
        require __DIR__ . '/api/testxpert.php';
    });
    
    Route::prefix('compressxpert')->group(function () {
        require __DIR__ . '/api/compressxpert.php';
    });
});