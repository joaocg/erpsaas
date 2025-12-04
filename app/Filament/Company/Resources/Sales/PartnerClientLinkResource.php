<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\PartnerClientLinkResource\Pages;
use App\Models\Common\Client;
use App\Models\Partner;
use App\Models\PartnerClientLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerClientLinkResource extends Resource
{
    protected static ?string $model = PartnerClientLink::class;
    protected static ?string $tenantRelationshipName = 'partnerClientLinks';

    protected static ?string $tenantRelationshipName = 'partnerClientLinks';

    protected static ?string $tenantRelationshipName = 'partnerClientLinks';

    // Configure tenancy resolution once to avoid duplicate property declarations.
    protected static ?string $tenantRelationshipName = 'partnerClientLinks';

    public static function getNavigationLabel(): string
    {
        return __('Partner Client Links');
    }

    public static function getModelLabel(): string
    {
        return __('Partner Client Link');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Partner Client Links');
    }

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Partner & Client'))
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label(__('Partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label(__('Client'))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('linked_at')
                            ->label(__('Linked at'))
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('Partner'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(__('Client'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('linked_at')
                    ->label(__('Linked at'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('partner_id')
                    ->label(__('Partner'))
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(__('Client'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartnerClientLinks::route('/'),
            'create' => Pages\CreatePartnerClientLink::route('/create'),
            'edit' => Pages\EditPartnerClientLink::route('/{record}/edit'),
        ];
    }
}
