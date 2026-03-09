<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        // User Management
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
        Route::post('/users/add', [AdminDashboardController::class, 'addUser'])->name('users.add');
        Route::post('/users/invite', [AdminDashboardController::class, 'inviteUser'])->name('users.invite');
        Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [AdminDashboardController::class, 'resetUserPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [AdminDashboardController::class, 'deleteUser'])->name('users.delete');

        // Invitations
        Route::get('/invitations', [AdminDashboardController::class, 'invitations'])->name('invitations');
        Route::delete('/invitations/{invitation}', [AdminDashboardController::class, 'revokeInvitation'])->name('invitations.revoke');
        Route::post('/invitations/org', [AdminDashboardController::class, 'inviteToOrganization'])->name('invitations.org');

        // Resource Oversight
        Route::get('/organizations', [AdminDashboardController::class, 'organizations'])->name('organizations');
        Route::get('/properties', [AdminDashboardController::class, 'properties'])->name('properties');
        Route::get('/properties/{property}', [AdminDashboardController::class, 'propertyDetail'])->name('properties.detail');
        Route::get('/sitemaps', [AdminDashboardController::class, 'sitemaps'])->name('sitemaps');
        Route::get('/schemas', [AdminDashboardController::class, 'schemas'])->name('schemas');

        // Global Intelligence
        Route::get('/keywords/research', [AdminDashboardController::class, 'keywordResearch'])->name('keywords.research');
        Route::get('/keywords/trends', [AdminDashboardController::class, 'keywordTrends'])->name('keywords.trends');

        // CMS & System
        Route::get('/config', [AdminDashboardController::class, 'getConfig'])->name('config.get');
        Route::post('/config', [AdminDashboardController::class, 'updateConfig'])->name('config.update');
        Route::post('/config/logo', [AdminDashboardController::class, 'uploadLogo'])->name('config.logo');
        Route::get('/services', [AdminDashboardController::class, 'services'])->name('services');
        Route::get('/logs', [AdminDashboardController::class, 'logs'])->name('logs');
    });
});
