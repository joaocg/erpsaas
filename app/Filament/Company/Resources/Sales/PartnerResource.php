<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    public static function getModelLabel(): string
    {
        return __('Partner');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Partners');
    }

    public static function getNavigationLabel(): string
    {
        return __('Partners');
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Partner Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('document')
                            ->label(__('Document'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('commission_percent')
                            ->label(__('Commission %'))
                            ->numeric()
                            ->step('0.01')
                            ->suffix('%')
                            ->default(20),
                        Forms\Components\Select::make('parent_id')
                            ->label(__('Parent partner'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Toggle::make('active')
                            ->label(__('Active'))
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document')
                    ->label(__('Document'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('commission_percent')
                    ->label(__('Commission %'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2) . '%' : null)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('clients_count')
                    ->label(__('Clients'))
                    ->counts('clients')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commissions_count')
                    ->label(__('Commissions'))
                    ->counts('commissions')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('Active')),
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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
