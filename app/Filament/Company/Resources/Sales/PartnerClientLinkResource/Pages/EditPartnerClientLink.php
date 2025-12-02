<?php

namespace App\Filament\Company\Resources\Sales\PartnerClientLinkResource\Pages;

use App\Filament\Company\Resources\Sales\PartnerClientLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartnerClientLink extends EditRecord
{
    protected static string $resource = PartnerClientLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
