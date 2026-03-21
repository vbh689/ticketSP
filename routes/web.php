<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
    ->whereNumber('ticket')
    ->name('tickets.show');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/bulk-status', [TicketController::class, 'bulkUpdateStatus'])->name('tickets.bulk-status.update');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->whereNumber('ticket')->name('tickets.claim');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->whereNumber('ticket')->name('tickets.status.update');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->whereNumber('ticket')->name('tickets.comments.store');

    Route::middleware('manager')->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')->name('employees.edit');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->name('employees.update');
    });
});
