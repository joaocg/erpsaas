<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferrerResource\Pages;
use App\Models\Referral\Referrer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferrerResource extends Resource
{
    protected static ?string $model = Referrer::class;

    protected static ?string $navigationGroup = 'Comissionamentos';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('Indicadores & Sócios');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employeeship_id')
                    ->label(__('Colaborador'))
                    ->relationship(
                        name: 'employeeship',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('company_id', auth()->user()?->current_company_id)
                            ->with('user')
                    )
                    ->searchable()
                    ->preload()
                    ->helperText(__('Selecione um colaborador já vinculado à empresa quando a indicação for interna.')),
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->requiredWithout('employeeship_id')
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('Tipo'))
                    ->options([
                        'office_owner' => 'Sócio',
                        'partner' => 'Parceiro',
                        'referrer' => 'Indicador',
                        'client' => 'Cliente',
                    ])
                    ->required()
                    ->default('referrer'),
                Forms\Components\TextInput::make('default_commission_percentage')
                    ->label(__('Comissão padrão (%)'))
                    ->numeric()
                    ->maxValue(100)
                    ->step(0.01),
                Forms\Components\TextInput::make('document')
                    ->label(__('Documento'))
                    ->maxLength(50),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email(),
                Forms\Components\TextInput::make('phone')
                    ->label(__('Telefone')),
                Forms\Components\TextInput::make('whatsapp')
                    ->label(__('WhatsApp')),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notas'))
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employeeship.name')
                    ->label(__('Colaborador'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')->label(__('Nome'))->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('Tipo'))
                    ->colors([
                        'primary' => 'referrer',
                        'success' => 'partner',
                        'warning' => 'office_owner',
                        'info' => 'client',
                    ])
                    ->formatStateUsing(fn (?string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('default_commission_percentage')
                    ->label(__('Comissão padrão'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Criado em'))->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Tipo'))
                    ->options([
                        'office_owner' => 'Sócio',
                        'partner' => 'Parceiro',
                        'referrer' => 'Indicador',
                        'client' => 'Cliente',
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
            'index' => Pages\ListReferrers::route('/'),
            'create' => Pages\CreateReferrer::route('/create'),
            'edit' => Pages\EditReferrer::route('/{record}/edit'),
        ];
    }
}
