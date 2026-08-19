<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\PdfController;
use App\Http\Controllers\Admin\PrintFlowController as AdminPrintFlowController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\PrintFlowPortalController;
use App\Http\Controllers\TestimonialSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TestimonialSubmissionController::class, 'create'])->name('testimonials.create');
Route::post('/depoimentos/enviar', [TestimonialSubmissionController::class, 'store'])->name('testimonials.store');
Route::get('/depoimentos/sucesso', [TestimonialSubmissionController::class, 'success'])->name('testimonials.success');

Route::get('/fluxos/{token}', [PrintFlowPortalController::class, 'show'])->name('print-flows.show');
Route::post('/fluxos/{token}/cartas/{testimonial}', [PrintFlowPortalController::class, 'review'])->name('print-flows.review');
Route::post('/fluxos/{token}/concluir-revisao', [PrintFlowPortalController::class, 'finishReview'])->name('print-flows.review.finish');
Route::get('/fluxos/{token}/imprimir', [PrintFlowPortalController::class, 'print'])->name('print-flows.print');
Route::post('/fluxos/{token}/concluir', [PrintFlowPortalController::class, 'complete'])->name('print-flows.complete');
Route::post('/fluxos/{token}/concluir-busca', [PrintFlowPortalController::class, 'completeSearch'])->name('print-flows.search.complete');

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

        Route::resource('/team', TeamMemberController::class)
            ->parameters(['team' => 'member'])
            ->except('show');

        Route::get('/print-flows', [AdminPrintFlowController::class, 'index'])->name('print-flows.index');
        Route::get('/print-flows/distribution-options', [AdminPrintFlowController::class, 'distributionOptions'])->name('print-flows.distribution-options');
        Route::get('/print-flows/create', [AdminPrintFlowController::class, 'create'])->name('print-flows.create');
        Route::post('/print-flows', [AdminPrintFlowController::class, 'store'])->name('print-flows.store');
        Route::get('/print-flows/{flow}/share', [AdminPrintFlowController::class, 'share'])->name('print-flows.share');
        Route::post('/print-flows/{flow}/renew', [AdminPrintFlowController::class, 'renew'])->name('print-flows.renew');
        Route::post('/print-flows/{flow}/cancel', [AdminPrintFlowController::class, 'cancel'])->name('print-flows.cancel');

        Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
        Route::get('/testimonials/{testimonial}', [TestimonialController::class, 'show'])->name('testimonials.show');
        Route::patch('/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('testimonials.update');
        Route::get('/testimonials/{testimonial}/photo', [TestimonialController::class, 'downloadPhoto'])->name('testimonials.photo');

        Route::get('/reports', [ReportController::class, 'participants'])->name('reports.index');
        Route::get('/reports/participants', [ReportController::class, 'participants'])->name('reports.participants');
        Route::get('/reports/participants/print', [ReportController::class, 'participantsPrint'])->name('reports.participants.print');
        Route::get('/reports/participants/excel', [ReportController::class, 'participantsExcel'])->name('reports.participants.excel');
        Route::get('/reports/testimonials', [ReportController::class, 'testimonials'])->name('reports.testimonials');
        Route::get('/reports/testimonials/print', [ReportController::class, 'testimonialsPrint'])->name('reports.testimonials.print');
        Route::get('/reports/testimonials/excel', [ReportController::class, 'testimonialsExcel'])->name('reports.testimonials.excel');

        Route::get('/pdf', [PdfController::class, 'index'])->name('pdf.index');
        Route::post('/pdf/participants/{participant}/generate', [PdfController::class, 'generate'])->name('pdf.generate');
        Route::get('/pdf/batches/{batch}/download', [PdfController::class, 'download'])->name('pdf.download');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/reset', [SettingController::class, 'reset'])->name('settings.reset');
    });
});
