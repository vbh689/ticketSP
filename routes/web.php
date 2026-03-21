<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SearchMaintenanceController;
use App\Http\Controllers\TagController;
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
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/bulk-status', [TicketController::class, 'bulkUpdateStatus'])->name('tickets.bulk-status.update');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->whereNumber('ticket')->name('tickets.claim');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->whereNumber('ticket')->name('tickets.status.update');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->whereNumber('ticket')->name('tickets.comments.store');

    Route::middleware('manager')->group(function (): void {
        Route::post('/admin/search/rebuild', [SearchMaintenanceController::class, 'rebuild'])->name('search.rebuild');
        Route::get('/admin/tags', [TagController::class, 'index'])->name('admin.tags.index');
        Route::post('/admin/ticket-categories', [TagController::class, 'storeTicketCategory'])->name('ticket-categories.store');
        Route::patch('/admin/ticket-categories/{ticketCategory}', [TagController::class, 'updateTicketCategory'])->whereNumber('ticketCategory')->name('ticket-categories.update');
        Route::delete('/admin/ticket-categories/{ticketCategory}', [TagController::class, 'destroyTicketCategory'])->whereNumber('ticketCategory')->name('ticket-categories.destroy');
        Route::post('/admin/tags', [TagController::class, 'storeTag'])->name('tags.store');
        Route::patch('/admin/tags/{tag}', [TagController::class, 'updateTag'])->whereNumber('tag')->name('tags.update');
        Route::delete('/admin/tags/{tag}', [TagController::class, 'destroyTag'])->whereNumber('tag')->name('tags.destroy');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('customers.edit');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')->name('employees.edit');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->name('employees.update');
    });
});
