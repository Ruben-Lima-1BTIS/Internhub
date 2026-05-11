<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HRController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoghourController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HourApprovalController;
use App\Http\Controllers\ReportApprovalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ExtrasController;
use App\Http\Controllers\GoogleController;

Route::get('/flyer', [ExtrasController::class, 'flyer'])->name('extras.flyer');
Route::get('/pricing-table', [ExtrasController::class, 'pricingTable'])->name('extras.pricing_table');

Route::middleware(['guest'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.showLogin');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});


Route::middleware(['auth'])->group(function () {
    Route::prefix('google')->name('google.')->group(function () {
        Route::post('/send', [GoogleController::class, 'sendEmail'])->name('send');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('/password/change', fn() => view('auth.change-password'))->name('password.change');
    Route::post('/password/change', [UserController::class, 'changePassword'])->name('password.change.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::middleware('role:admin')->group(function () {

        Route::get('/hr', [HRController::class, 'index'])->name('hr.index');
        Route::get('/hr/create', [HRController::class, 'create'])->name('hr.create-records');
        Route::post('/hr/company', [HRController::class, 'createCompany'])->name('hr.company.create');
        Route::post('/hr/class', [HRController::class, 'createClass'])->name('hr.class.create');
        Route::post('/hr/internship', [HRController::class, 'createInternship'])->name('hr.internship.create');
        Route::post('/hr/createUser', [HRController::class, 'createUser'])->name('hr.user.create');
        Route::post('/hr/assignUser', [HRController::class, 'assignUserInternship'])->name('hr.user.assignUser');
        Route::get('/hr/delete', [HRController::class, 'delete'])->name('hr.delete-records');
        Route::post('/hr/delete/student', [HRController::class, 'deleteStudent'])->name('hr.student.delete');
        Route::post('/hr/delete/supervisor', [HRController::class, 'deleteSupervisor'])->name('hr.supervisor.delete');
        Route::post('/hr/delete/coordinator', [HRController::class, 'deleteCoordinator'])->name('hr.coordinator.delete');
        Route::post('/hr/delete/company', [HRController::class, 'deleteCompany'])->name('hr.company.delete');
        Route::post('/hr/delete/class', [HRController::class, 'deleteClass'])->name('hr.class.delete');
        Route::post('/hr/delete/internship', [HRController::class, 'deleteInternship'])->name('hr.internship.delete');
        Route::post('/hr/delete/unassign', [HRController::class, 'unassignUserInternship'])->name('hr.unassign');


        Route::prefix('google')->name('google.')->group(function () {
            Route::get('/', [GoogleController::class, 'index'])->name('index');
            Route::get('/connect', [GoogleController::class, 'connect'])->name('connect');
            Route::get('/callback', [GoogleController::class, 'callback'])->name('callback');
            Route::get('/signout', [GoogleController::class, 'signout'])->name('signout');
            Route::get('/labels', [GoogleController::class, 'listLabels'])->name('labels');
            Route::post('/send', [GoogleController::class, 'sendEmail'])->name('send');
        });
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/myhours', [LoghourController::class, 'index'])->name('student.hours');
        Route::post('/loghours', [LoghourController::class, 'store'])->name('log.hours');
        Route::get('/reports', [ReportController::class, 'index'])->name('student.reports');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    });

    Route::middleware('role:coordinator')->group(function () {
        Route::get('/report-approval', [ReportApprovalController::class, 'index'])->name('coordinator.reports.index');
        Route::post('/report-approval/{id}/approve', [ReportApprovalController::class, 'approve'])->name('report.approve');
        Route::post('/report-approval/{id}/reject', [ReportApprovalController::class, 'reject'])->name('report.reject');
    });

    Route::middleware('role:supervisor')->group(function () {
        Route::get('/hour-approval', [HourApprovalController::class, 'index'])->name('supervisor.hour_approval');
        Route::post('/hour-approval/{id}/approve', [HourApprovalController::class, 'approve'])->name('hour.approve');
        Route::post('/hour-approval/{id}/reject', [HourApprovalController::class, 'reject'])->name('hour.reject');
    });
});