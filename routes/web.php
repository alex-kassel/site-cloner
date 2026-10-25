<?php

declare(strict_types=1);

use AlexKassel\SiteCloner\Http\Controllers\DashboardController;
use AlexKassel\SiteCloner\Http\Controllers\ViewerController;
use Illuminate\Support\Facades\Route;

$prefix = config('site-cloner.route_prefix', 'site-cloner');
$middleware = config('site-cloner.middleware', ['web']);

Route::prefix($prefix)->middleware($middleware)->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('site-cloner.index');
    Route::post('/check', [DashboardController::class, 'check'])->name('site-cloner.check');
    Route::post('/start', [DashboardController::class, 'start'])->name('site-cloner.start');
    Route::get('/status/{siteId}', [DashboardController::class, 'status'])->name('site-cloner.status');
    Route::get('/resources/{siteId}', [DashboardController::class, 'resources'])->name('site-cloner.resources');
    Route::get('/progress/{siteId}', [DashboardController::class, 'progress'])->name('site-cloner.progress');
    Route::delete('/sites/{siteId}', [DashboardController::class, 'destroy'])->name('site-cloner.destroy');
    Route::get('/view/{siteId}/{path?}', [ViewerController::class, 'serve'])->where('path', '.*')->name('site-cloner.view');
});
