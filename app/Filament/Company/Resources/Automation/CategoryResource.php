<?php

namespace App\Filament\Company\Resources\Automation;

use App\Filament\Company\Resources\Automation\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function getNavigationLabel(): string
    {
        return __('Categories');
    }

    protected static ?string $navigationGroup = 'Automação & WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Categoria')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id())
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'expense' => 'Despesa',
                                'income' => 'Receita',
                                'medical' => 'Médico',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('icon')
                            ->label('Ícone (heroicon)')
                            ->maxLength(255)
                            ->placeholder('heroicon-o-document-text'),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Cor'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                        'medical' => 'Médico',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Ícone')
                    ->toggleable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Cor')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'expense' => 'Despesa',
                        'income' => 'Receita',
                        'medical' => 'Médico',
                    ]),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
