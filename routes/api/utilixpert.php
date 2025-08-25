<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtiliXpert\UnitConverterController;
use App\Http\Controllers\Api\UtiliXpert\QRGeneratorController;
use App\Http\Controllers\Api\UtiliXpert\UUIDGeneratorController;
use App\Http\Controllers\Api\UtiliXpert\RandomGeneratorController;

// Unit Converter
Route::controller(UnitConverterController::class)->group(function () {
    Route::post('/unit-converter', 'convert');
    Route::get('/unit-converter/units', 'getSupportedUnits');
});

// QR Generator
Route::controller(QRGeneratorController::class)->group(function () {
    Route::post('/qr-generator', 'generate');
    Route::get('/qr-generator/options', 'getSupportedOptions');
});

// UUID Generator
Route::controller(UUIDGeneratorController::class)->group(function () {
    Route::post('/uuid-generator', 'generate');
    Route::get('/uuid-generator/options', 'getSupportedOptions');
});

// Random Generator
Route::controller(RandomGeneratorController::class)->group(function () {
    Route::post('/random-generator', 'generate');
});