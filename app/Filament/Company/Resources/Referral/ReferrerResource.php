<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferrerResource\Pages;
use App\Models\Referral\Referrer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReferrerResource extends Resource
{
    protected static ?string $model = Referrer::class;

    protected static ?string $slug = 'referrals/referrers';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Indicadores';

    public static function form(Form $form): Form
    {
        $companyId = Auth::user()?->currentCompany?->id;

        return $form
            ->schema([
                Forms\Components\Hidden::make('company_id')
                    ->default($companyId)
                    ->required(),
                Forms\Components\Section::make('Dados do indicador')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Colaborador interno')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->whereHas('companies', fn ($relationQuery) => $relationQuery->where('company_id', $companyId));
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Selecione um colaborador existente que possa receber comissão.'),
                        Forms\Components\Select::make('contact_id')
                            ->label('Contato externo')
                            ->relationship(
                                name: 'contact',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->where('company_id', $companyId);
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Use quando o indicador não for colaborador interno.'),
                        Forms\Components\Select::make('parent_id')
                            ->label('Indicador superior')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->where('company_id', $companyId);
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('default_commission_rate')
                            ->label('Percentual padrão (%)')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Indicador')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Superior')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_commission_rate')
                    ->label('% padrão')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = Auth::user()?->currentCompany?->id;

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }
}
