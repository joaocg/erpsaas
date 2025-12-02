<?php

namespace App\Filament\Company\Resources\Referral\ReferrerResource\Pages;

use App\Filament\Company\Resources\Referral\ReferrerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferrers extends ListRecords
{
    protected static string $resource = ReferrerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
