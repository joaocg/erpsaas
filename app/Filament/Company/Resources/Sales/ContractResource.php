<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ContractResource\Pages;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $slug = 'sales/contracts';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getNavigationLabel(): string
    {
        return __('Contracts');
    }

    public static function getModelLabel(): string
    {
        return __('Contract');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Contracts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Contract Details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('client_id')
                            ->label(__('Client'))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start date')),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make(__('Payment Structure'))
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label(__('Total amount'))
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('entry_amount')
                            ->label(__('Entry (down payment)'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('installment_count')
                            ->label(__('Installments'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total amount'))
                    ->money('usd'),
                Tables\Columns\TextColumn::make('entry_amount')
                    ->label(__('Entry'))
                    ->money('usd'),
                Tables\Columns\TextColumn::make('installment_count')
                    ->label(__('Installments')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
