<?php

namespace App\Filament\Exports\Common;

use App\Models\Common\Client;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('company.name'),
            ExportColumn::make('name'),
            ExportColumn::make('currency_code'),
            ExportColumn::make('account_number'),
            ExportColumn::make('website'),
            ExportColumn::make('notes'),
            ExportColumn::make('primaryContact.full_name')
                ->label('Primary Contact'),
            ExportColumn::make('primaryContact.email')
                ->label('Primary Contact Email'),
            ExportColumn::make('primaryContact.phones')
                ->label('Primary Contact Phones') // TODO: Format better
                ->listAsJson(),
            ExportColumn::make('billingAddress.address_string')
                ->label('Billing Address'),
            ExportColumn::make('created_by'),
            ExportColumn::make('updated_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your client export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
