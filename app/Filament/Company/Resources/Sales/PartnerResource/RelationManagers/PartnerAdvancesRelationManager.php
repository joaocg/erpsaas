<?php

namespace App\Filament\Company\Resources\Sales\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerAdvancesRelationManager extends RelationManager
{
    protected static string $relationship = 'advances';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('advanced_at')
                    ->label(__('Advanced at')),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('usd'),
                Tables\Columns\TextColumn::make('advanced_at')
                    ->label(__('Advanced at'))
                    ->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
