<?php

use App\Http\Controllers\DocumentPrintController;
use App\Http\Middleware\AllowSameOriginFrame;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(Filament::getDefaultPanel()->getUrl());
});

Route::middleware(['auth'])->group(function () {
    Route::get('documents/{documentType}/{id}/print', [DocumentPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.print');

    Route::get('/company/{tenant}/sales/contracts', function (string $tenant) {
        return redirect()->route('filament.company.resources.sales.invoices.index', ['tenant' => $tenant]);
    })->name('filament.company.resources.sales.contracts.index');

    Route::redirect(
        '/company/sales/contracts',
        fn () => route('filament.company.resources.sales.invoices.index')
    );
});
