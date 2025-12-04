<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClientExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category')
                    ->label(__('Category'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('incurred_at')
                    ->label(__('Date')),
                Forms\Components\Toggle::make('reimbursable')
                    ->label(__('Reimbursable'))
                    ->default(true),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label(__('Category'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('usd'),
                Tables\Columns\TextColumn::make('incurred_at')
                    ->label(__('Date'))
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
