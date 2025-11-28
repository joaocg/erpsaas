<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\ClearLog;
use App\Console\Commands\FirePlaidWebhook;
use App\Console\Commands\InitializeCurrencies;
use App\Console\Commands\SyncWahaWebhook;
use App\Console\Commands\TriggerRecurringInvoiceGeneration;
use App\Console\Commands\UpdateOverdueInvoices;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withCommands([
        ClearLog::class,
        FirePlaidWebhook::class,
        InitializeCurrencies::class,
        SyncWahaWebhook::class,
        TriggerRecurringInvoiceGeneration::class,
        UpdateOverdueInvoices::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
