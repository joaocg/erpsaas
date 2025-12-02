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

    public static function getNavigationLabel(): string
    {
        return translate('Partner Client Links');
    }

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partner & Client')
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label('Partner')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('linked_at')
                            ->label('Linked at')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('linked_at')
                    ->label('Linked at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('partner_id')
                    ->label('Partner')
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
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
