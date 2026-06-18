<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('user')->group(function () {
    // User accessible
    Route::livewire('/home', 'pages::admin.dashboard')->name('dashboard.user');
    Route::livewire('/patient', 'pages::patient.index')->name('patients.index');
    Route::livewire('/drugs', 'pages::drugs.index')->name('drugs.index');
    Route::livewire('/medical-records', 'pages::medical_records.index')->name('medical_records.index');
    Route::livewire('/pregnancy', 'pages::pregnancy.index')->name('pregnancy.index');
    Route::livewire('/delivery', 'pages::delivery.index')->name('delivery.index');
    Route::livewire('/immunization', 'pages::immunization.index')->name('immunization.index');
    Route::livewire('/kb', 'pages::kb.index')->name('kb.index');

    // Admin + Employee accessible

    // Admin only routes
});

Route::middleware('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard.index');
    Route::livewire('/user', 'pages::users.index')->name('users.index')->middleware('admin');
    Route::livewire('/laporan/kb', 'pages::kb.laporan')->name('laporan.kb');
    Route::livewire('/laporan/pasien', 'pages::patient.laporan')->name('laporan.pasien');
    Route::livewire('/laporan/obat', 'pages::drugs.laporan')->name('laporan.obat');
    Route::livewire('/laporan/rekam-medis', 'pages::medical_records.laporan')->name('laporan.rekam-medis');
    Route::livewire('/laporan/kehamilan', 'pages::pregnancy.laporan')->name('laporan.kehamilan');
    Route::livewire('/laporan/persalinan', 'pages::delivery.laporan')->name('laporan.persalinan');
    Route::livewire('/laporan/imunisasi', 'pages::immunization.laporan')->name('laporan.imunisasi');
});
