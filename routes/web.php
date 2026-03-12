<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('invoices', InvoiceController::class);

    Route::get('/invoices/{invoice}/print/instruction', [InvoiceController::class, 'printInstruction'])
        ->name('invoices.print.instruction');

    Route::get('/invoices/{invoice}/print/logo', [InvoiceController::class, 'printLogo'])
        ->name('invoices.print.logo');

    Route::get('/invoices/{invoice}/print/logo-pdf', [InvoiceController::class, 'logoPdf'])
        ->name('invoices.print.logoPdf');

    Route::get('/invoices/{invoice}/print/packaging-slip', [InvoiceController::class, 'packagingSlipPdf'])
        ->name('invoices.print.packagingSlip');
});

Route::get('/packaging-slips', function () {
    $ids = request('ids', []);

    return app(InvoiceController::class)->packagingSlipPdfBulk($ids);
})->name('invoices.packaging-slips.bulk');

require __DIR__.'/settings.php';
