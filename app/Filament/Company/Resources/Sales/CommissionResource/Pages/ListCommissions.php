<?php

namespace App\Filament\Company\Resources\Sales\CommissionResource\Pages;

use App\Filament\Company\Resources\Sales\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
