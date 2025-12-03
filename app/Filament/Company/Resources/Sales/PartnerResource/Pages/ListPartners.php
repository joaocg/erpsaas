<?php

namespace App\Filament\Company\Resources\Sales\PartnerResource\Pages;

use App\Filament\Company\Resources\Sales\PartnerResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'active' => Tab::make(__('Active'))
                ->modifyQueryUsing(fn ($query) => $query->where('active', true)),
            'inactive' => Tab::make(__('Inactive'))
                ->modifyQueryUsing(fn ($query) => $query->where('active', false)),
        ];
    }
}
