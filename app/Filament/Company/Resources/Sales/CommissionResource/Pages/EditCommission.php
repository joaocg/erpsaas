<?php

namespace App\Filament\Company\Resources\Sales\CommissionResource\Pages;

use App\Filament\Company\Resources\Sales\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommission extends EditRecord
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
