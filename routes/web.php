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
        return redirect()->route('filament.company.resources.sales.recurring-invoices.index', ['tenant' => $tenant]);
    })->name('filament.company.resources.sales.contracts.index');

    Route::get('/company/{tenant}/sales/services', function (string $tenant) {
        $connectedAccountsRoute = 'filament.company.pages.service.connected-account';
        $liveCurrencyRoute = 'filament.company.pages.service.live-currency';

        if (Route::has($connectedAccountsRoute)) {
            return redirect()->route($connectedAccountsRoute, ['tenant' => $tenant]);
        }

        return redirect()->route($liveCurrencyRoute, ['tenant' => $tenant]);
    })->name('filament.company.resources.sales.services.index');

    Route::redirect(
        '/company/sales/contracts',
        fn () => route('filament.company.resources.sales.recurring-invoices.index')
    );

    Route::redirect(
        '/company/sales/services',
        function () {
            $connectedAccountsRoute = 'filament.company.pages.service.connected-account';
            $liveCurrencyRoute = 'filament.company.pages.service.live-currency';

            if (Route::has($connectedAccountsRoute)) {
                return route($connectedAccountsRoute);
            }

            return route($liveCurrencyRoute);
        }
    );
});
