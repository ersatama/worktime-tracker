<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');
Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');

Route::post('/employees/{employee}/timer/start', [TimerController::class, 'start'])->name('timer.start');
Route::post('/employees/{employee}/timer/stop', [TimerController::class, 'stop'])->name('timer.stop');

Route::get('/reports', ReportController::class)->name('reports.index');
