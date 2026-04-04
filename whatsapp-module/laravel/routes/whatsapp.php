<?php

/**
 * WhatsApp module routes.
 *
 * Include from routes/web.php inside a `Route::middleware('web')->group()` (or merge this
 * file's `web` middleware with your stack):
 *
 *     require base_path('whatsapp-module/laravel/routes/whatsapp.php');
 *
 * If your project has no Laravel Gate for `manage-whatsapp`, register one in
 * App\Providers\AuthServiceProvider::boot():
 *
 *     Gate::define('manage-whatsapp', function ($user) {
 *         return $user->hasPermissionTo('manage-whatsapp'); // Spatie example
 *     });
 *
 * Or replace `can:manage-whatsapp` in App\Http\Controllers\WhatsApp\WhatsAppController
 * with `permission:your-permission` / `role:admin` / or remove and use only `auth`.
 */

use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/', [WhatsAppController::class, 'index'])->name('index');
    Route::get('/settings', [WhatsAppController::class, 'settings'])->name('settings');
    Route::post('/settings', [WhatsAppController::class, 'saveSettings'])->name('save-settings');

    Route::get('/status', [WhatsAppController::class, 'status'])->name('status');
    Route::post('/initialize', [WhatsAppController::class, 'initialize'])->name('initialize');
    Route::post('/logout', [WhatsAppController::class, 'logout'])->name('logout');
    Route::post('/send', [WhatsAppController::class, 'send'])->name('send');
    Route::post('/send-bulk', [WhatsAppController::class, 'sendBulk'])->name('send-bulk');
    Route::post('/validate-number', [WhatsAppController::class, 'validateNumber'])->name('validate-number');
    Route::get('/health', [WhatsAppController::class, 'health'])->name('health');
    Route::get('/stats', [WhatsAppController::class, 'stats'])->name('stats');

    Route::get('/logs', [WhatsAppController::class, 'logs'])->name('logs');
    Route::get('/logs/export', [WhatsAppController::class, 'exportLogs'])->name('export-logs');
    Route::post('/logs/bulk-delete', [WhatsAppController::class, 'bulkDeleteLogs'])->name('logs.bulk-delete');

    Route::get('/conversations', [WhatsAppController::class, 'conversations'])->name('conversations');
    Route::get('/conversation/{phone}', [WhatsAppController::class, 'conversation'])
        ->where('phone', '[0-9]+')
        ->name('conversation');

    Route::post('/templates', [WhatsAppController::class, 'storeTemplate'])->name('templates.store');
    Route::put('/templates/{id}', [WhatsAppController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{id}', [WhatsAppController::class, 'destroyTemplate'])->name('templates.destroy');
    Route::post('/templates/preview', [WhatsAppController::class, 'previewTemplate'])->name('templates.preview');
});
