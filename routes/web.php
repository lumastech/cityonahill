<?php

use App\Http\Controllers\GradeController;
use App\Http\Controllers\LinkSubjectController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Module 1 — School & Class Structure
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('grades', GradeController::class)->except(['show', 'create']);
    Route::resource('streams', StreamController::class)->except(['create']);
    Route::resource('subjects', SubjectController::class)->except(['show', 'create']);
    Route::post('grades/{grade}/subjects', LinkSubjectController::class)
        ->name('grades.subjects.link');
    Route::resource('timetable', TimetableController::class)
        ->only(['index', 'store', 'destroy']);
});
