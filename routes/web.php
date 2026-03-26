<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PdfController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\TestimonialSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TestimonialSubmissionController::class, 'create'])->name('testimonials.create');
Route::post('/depoimentos/enviar', [TestimonialSubmissionController::class, 'store'])->name('testimonials.store');
Route::get('/depoimentos/sucesso', [TestimonialSubmissionController::class, 'success'])->name('testimonials.success');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login/enviar-codigo', [AdminAuthController::class, 'sendCode'])->name('login.send');
    Route::get('/login/verificar-codigo', [AdminAuthController::class, 'showVerifyForm'])->name('login.verify');
    Route::post('/login/verificar-codigo', [AdminAuthController::class, 'verifyCode'])->name('login.verify.submit');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
        Route::get('/participants/create', [ParticipantController::class, 'create'])->name('participants.create');
        Route::get('/participants/import', [ParticipantController::class, 'importForm'])->name('participants.import.form');
        Route::post('/participants/import', [ParticipantController::class, 'importStore'])->name('participants.import.store');
        Route::get('/participants/template', [ParticipantController::class, 'downloadTemplate'])->name('participants.template');
        Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
        Route::get('/participants/{participant}/edit', [ParticipantController::class, 'edit'])->name('participants.edit');
        Route::put('/participants/{participant}', [ParticipantController::class, 'update'])->name('participants.update');
        Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
        Route::get('/testimonials/{testimonial}', [TestimonialController::class, 'show'])->name('testimonials.show');
        Route::patch('/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('testimonials.update');
        Route::get('/testimonials/{testimonial}/photo', [TestimonialController::class, 'downloadPhoto'])->name('testimonials.photo');

        Route::get('/pdf', [PdfController::class, 'index'])->name('pdf.index');
        Route::post('/pdf/participants/{participant}/generate', [PdfController::class, 'generate'])->name('pdf.generate');
        Route::get('/pdf/batches/{batch}/download', [PdfController::class, 'download'])->name('pdf.download');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
