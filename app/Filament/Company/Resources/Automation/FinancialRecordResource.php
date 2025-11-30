<?php

namespace App\Filament\Company\Resources\Automation;

use App\Filament\Company\Resources\Automation\FinancialRecordResource\Pages;
use App\Models\FinancialRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinancialRecordResource extends Resource
{
    protected static ?string $model = FinancialRecord::class;

    public static function getNavigationLabel(): string
    {
        return __('Financial Records');
    }

    protected static ?string $navigationGroup = 'Automação & WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true),
                Forms\Components\Section::make('Lançamento')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'income' => 'Receita',
                                'expense' => 'Despesa',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->numeric()
                            ->required()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('currency')
                            ->label('Moeda')
                            ->maxLength(3)
                            ->default('BRL'),
                        Forms\Components\DatePicker::make('occurred_on')
                            ->label('Data')
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? 'Sem nome')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('attachment_id')
                            ->label('Anexo')
                            ->relationship('attachment', 'original_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->original_name ?? 'Sem nome')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->formatStateUsing(fn (?string $state) => $state === 'income' ? 'Receita' : 'Despesa'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency ?? 'BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('occurred_on')
                    ->label('Data')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attachment.original_name')
                    ->label('Anexo')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Categoria'),
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
            'index' => Pages\ListFinancialRecords::route('/'),
            'create' => Pages\CreateFinancialRecord::route('/create'),
            'edit' => Pages\EditFinancialRecord::route('/{record}/edit'),
        ];
    }
}
