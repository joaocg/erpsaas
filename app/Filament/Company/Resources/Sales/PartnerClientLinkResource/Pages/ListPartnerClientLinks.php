<?php

namespace App\Filament\Company\Resources\Sales\PartnerClientLinkResource\Pages;

use App\Filament\Company\Resources\Sales\PartnerClientLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerClientLinks extends ListRecords
{
    protected static string $resource = PartnerClientLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
