<?php

namespace App\Providers;

use App\Http\Responses\LoginRedirectResponse;
use App\Models\ClientExpense;
use App\Models\Export;
use App\Models\Import;
use App\Models\Notification;
use App\Models\PartnerAdvance;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServiceActivity;
use App\Services\DateRangeService;
use App\Observers\ClientExpenseObserver;
use App\Observers\ContractObserver;
use App\Observers\PartnerAdvanceObserver;
use App\Observers\ServiceActivityObserver;
use App\Observers\ServiceObserver;
use Filament\Actions\Exports\Models\Export as BaseExport;
use Filament\Actions\Imports\Models\Import as BaseImport;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DateRangeService::class);
        $this->app->singleton(LoginResponse::class, LoginRedirectResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bind custom Import and Export models
        $this->app->bind(BaseImport::class, Import::class);
        $this->app->bind(BaseExport::class, Export::class);

        // Bind custom Notification model
        $this->app->bind(DatabaseNotification::class, Notification::class);

        Notifications::alignment(Alignment::Center);

        FilamentAsset::register([
            Js::make('top-navigation', __DIR__ . '/../../resources/js/top-navigation.js'),
            Js::make('history-fix', __DIR__ . '/../../resources/js/history-fix.js'),
            Js::make('custom-print', __DIR__ . '/../../resources/js/custom-print.js'),
        ]);

        Service::observe(ServiceObserver::class);
        ServiceActivity::observe(ServiceActivityObserver::class);
        ClientExpense::observe(ClientExpenseObserver::class);
        Contract::observe(ContractObserver::class);
        PartnerAdvance::observe(PartnerAdvanceObserver::class);
    }
}
