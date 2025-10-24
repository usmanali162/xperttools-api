<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MediaXpert\VideoDownloaderController;

// Video Downloader - Unified tool for all platforms
Route::controller(VideoDownloaderController::class)->group(function () {
    Route::post('/video-downloader', 'download')->name('video-downloader.download');
    Route::post('/video-downloader/info', 'getVideoInfo')->name('video-downloader.info');
    Route::get('/video-downloader/options', 'getOptions')->name('video-downloader.options');
    Route::post('/video-downloader/detect-platform', 'detectPlatform')->name('video-downloader.detect');
    Route::get('/video-downloader/history', 'getHistory')->name('video-downloader.history');
    Route::get('/video-downloader/usage', 'getUsageStats')->name('video-downloader.usage');
});

// File download route with XpertTools branding
Route::get('/downloads/{filename}', function ($filename) {
    $path = storage_path('app/downloads/' . $filename);
    
    if (!file_exists($path)) {
        abort(404, 'File not found');
    }
    
    // Add XpertTools branding to download headers
    return response()->download($path, $filename, [
        'X-Downloaded-Via' => 'XpertTools.com',
        'X-Tool-Used' => 'MediaXpert Video Downloader',
    ]);
})->where('filename', '.*');

// SEO-friendly aliases that redirect to main tool
// These routes help with SEO for platform-specific searches
Route::redirect('/youtube-video-downloader', '/video-downloader?platform=youtube');
Route::redirect('/facebook-video-downloader', '/video-downloader?platform=facebook');
Route::redirect('/instagram-video-downloader', '/video-downloader?platform=instagram');
Route::redirect('/twitter-video-downloader', '/video-downloader?platform=twitter');
Route::redirect('/tiktok-video-downloader', '/video-downloader?platform=tiktok');
Route::redirect('/vimeo-video-downloader', '/video-downloader?platform=vimeo');
Route::redirect('/dailymotion-video-downloader', '/video-downloader?platform=dailymotion');
Route::redirect('/linkedin-video-downloader', '/video-downloader?platform=linkedin');

// Future MediaXpert tools (placeholders for now)
// Route::controller(Mp3ConverterController::class)->group(function () {
//     Route::post('/mp3-converter', 'convert');
//     Route::get('/mp3-converter/options', 'getOptions');
// });

// Route::controller(Mp4ConverterController::class)->group(function () {
//     Route::post('/mp4-converter', 'convert');
//     Route::get('/mp4-converter/options', 'getOptions');
// });

// Route::controller(GifConverterController::class)->group(function () {
//     Route::post('/gif-converter', 'convert');
//     Route::get('/gif-converter/options', 'getOptions');
// });

// Route::controller(AudioConverterController::class)->group(function () {
//     Route::post('/audio-converter', 'convert');
//     Route::get('/audio-converter/options', 'getOptions');
// });

// Route::controller(VideoCompressorController::class)->group(function () {
//     Route::post('/video-compressor', 'compress');
//     Route::get('/video-compressor/options', 'getOptions');
// });