<?php

namespace App\Filament\Company\Resources\Automation;

use App\Filament\Company\Resources\Automation\MedicalAppointmentResource\Pages;
use App\Models\MedicalAppointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalAppointmentResource extends Resource
{
    protected static ?string $model = MedicalAppointment::class;

    public static function getNavigationLabel(): string
    {
        return __('Medical Appointments');
    }

    protected static ?string $navigationGroup = 'Automação & WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true),
                Forms\Components\Section::make('Consulta')
                    ->schema([
                        Forms\Components\TextInput::make('provider_name')
                            ->label('Médico / Clínica')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('specialty')
                            ->label('Especialidade')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('occurred_on')
                            ->label('Data')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'scheduled' => 'Agendado',
                                'completed' => 'Concluído',
                                'canceled' => 'Cancelado',
                            ])
                            ->default('scheduled'),
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
                            ->label('Anotações')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider_name')
                    ->label('Médico / Clínica')
                    ->searchable(),
                Tables\Columns\TextColumn::make('specialty')
                    ->label('Especialidade'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'canceled',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'scheduled' => 'Agendado',
                        'completed' => 'Concluído',
                        'canceled' => 'Cancelado',
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
                        'scheduled' => 'Agendado',
                        'completed' => 'Concluído',
                        'canceled' => 'Cancelado',
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
            'index' => Pages\ListMedicalAppointments::route('/'),
            'create' => Pages\CreateMedicalAppointment::route('/create'),
            'edit' => Pages\EditMedicalAppointment::route('/{record}/edit'),
        ];
    }
}
