<?php

namespace App\Filament\Company\Resources\Referral\ReferralCaseResource\Pages;

use App\Filament\Company\Resources\Referral\ReferralCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralCases extends ListRecords
{
    protected static string $resource = ReferralCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
