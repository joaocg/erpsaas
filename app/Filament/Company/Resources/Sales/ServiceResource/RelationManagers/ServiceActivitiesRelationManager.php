<?php

namespace App\Filament\Company\Resources\Sales\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->label(__('Type'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('cost')
                    ->label(__('Cost'))
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('activity_date')
                    ->label(__('Date')),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type')),
                Tables\Columns\TextColumn::make('cost')
                    ->label(__('Cost'))
                    ->money('usd'),
                Tables\Columns\TextColumn::make('activity_date')
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
