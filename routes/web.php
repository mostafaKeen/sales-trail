<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/tenants');
});

Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Bitrix24 OAuth & Installation
Route::post('/bitrix24/callback', [\App\Http\Controllers\Bitrix24Controller::class, 'installationCallback'])->name('bitrix24.callback');
Route::match(['get', 'post'], '/bitrix24/oauth/callback', [\App\Http\Controllers\Bitrix24Controller::class, 'handleOAuthCallback'])->name('bitrix24.oauth.callback');
Route::match(['get', 'post'], '/bitrix24/config', [\App\Http\Controllers\Bitrix24Controller::class, 'showConfig'])->name('bitrix24.config.show');
Route::post('/bitrix24/config/save', [\App\Http\Controllers\Bitrix24Controller::class, 'saveConfig'])->name('bitrix24.config.save');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('admin.tenants.index');
    Route::post('/tenants/{id}/toggle-status', [\App\Http\Controllers\Admin\TenantController::class, 'toggleStatus'])->name('admin.tenants.toggle-status');
    Route::post('/tenants/{id}/update-integrations', [\App\Http\Controllers\Admin\TenantController::class, 'updateIntegrations'])->name('admin.tenants.update-integrations');
    Route::get('/tenants/{id}/logs', [\App\Http\Controllers\Admin\TenantController::class, 'logs'])->name('admin.tenants.logs');
    Route::get('/tenants/{id}/bitrix24/connect', [\App\Http\Controllers\Bitrix24Controller::class, 'startOAuth'])->name('bitrix24.start.oauth');
});

