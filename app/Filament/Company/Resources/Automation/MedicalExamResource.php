<?php

namespace App\Filament\Company\Resources\Automation;

use App\Filament\Company\Resources\Automation\MedicalExamResource\Pages;
use App\Models\MedicalExam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalExamResource extends Resource
{
    protected static ?string $model = MedicalExam::class;

    public static function getNavigationLabel(): string
    {
        return __('Medical Exams');
    }

    protected static ?string $navigationGroup = 'Automação & WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true),
                Forms\Components\Section::make('Exame')
                    ->schema([
                        Forms\Components\TextInput::make('exam_type')
                            ->label('Tipo de exame')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('lab_name')
                            ->label('Laboratório')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('occurred_on')
                            ->label('Data')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'requested' => 'Solicitado',
                                'in_progress' => 'Em andamento',
                                'completed' => 'Concluído',
                            ])
                            ->default('requested'),
                        Forms\Components\Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('attachment_id')
                            ->label('Anexo')
                            ->relationship('attachment', 'original_name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('results_json')
                            ->label('Resultados (JSON)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam_type')
                    ->label('Exame')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lab_name')
                    ->label('Laboratório'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'requested',
                        'info' => 'in_progress',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'requested' => 'Solicitado',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluído',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('occurred_on')
                    ->label('Data')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge(),
                Tables\Columns\TextColumn::make('attachment.original_name')
                    ->label('Anexo')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'requested' => 'Solicitado',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluído',
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
            'index' => Pages\ListMedicalExams::route('/'),
            'create' => Pages\CreateMedicalExam::route('/create'),
            'edit' => Pages\EditMedicalExam::route('/{record}/edit'),
        ];
    }
}
