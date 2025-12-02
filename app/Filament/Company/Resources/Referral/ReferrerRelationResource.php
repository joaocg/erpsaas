<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferrerRelationResource\Pages;
use App\Models\Referral\ReferrerRelation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferrerRelationResource extends Resource
{
    protected static ?string $model = ReferrerRelation::class;

    protected static ?string $navigationGroup = 'Comissionamentos';

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function getNavigationLabel(): string
    {
        return __('Rede de Indicação');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('parent_id')
                ->label(__('Indicador acima'))
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('child_id')
                ->label(__('Indicador abaixo'))
                ->relationship('child', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('commission_percentage')
                ->label(__('Comissão (%)'))
                ->numeric()
                ->step(0.01)
                ->maxValue(100),
            Forms\Components\Toggle::make('active')
                ->label(__('Ativo'))
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parent.name')->label(__('Pai')),
                Tables\Columns\TextColumn::make('child.name')->label(__('Filho')),
                Tables\Columns\TextColumn::make('commission_percentage')
                    ->label(__('Comissão'))
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('active')->boolean()->label(__('Ativo')),
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
            'index' => Pages\ListReferrerRelations::route('/'),
            'create' => Pages\CreateReferrerRelation::route('/create'),
            'edit' => Pages\EditReferrerRelation::route('/{record}/edit'),
        ];
    }
}
