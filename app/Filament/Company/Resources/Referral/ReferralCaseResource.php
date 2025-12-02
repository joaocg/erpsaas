<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferralCaseResource\Pages;
use App\Models\Referral\ReferralCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReferralCaseResource extends Resource
{
    protected static ?string $model = ReferralCase::class;

    protected static ?string $slug = 'referrals/cases';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Casos de indicação';

    public static function form(Form $form): Form
    {
        $companyId = Auth::user()?->currentCompany?->id;

        return $form
            ->schema([
                Forms\Components\Hidden::make('company_id')
                    ->default($companyId)
                    ->required(),
                Forms\Components\Section::make('Dados do caso')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('estimated_value')
                            ->label('Valor estimado')
                            ->numeric()
                            ->prefix('$')
                            ->step('0.01'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'aberto' => 'Aberto',
                                'em_andamento' => 'Em andamento',
                                'encerrado' => 'Encerrado',
                            ])
                            ->default('aberto')
                            ->required(),
                        Forms\Components\DatePicker::make('opened_at')
                            ->label('Data de abertura')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('closed_at')
                            ->label('Data de fechamento'),
                    ])->columns(2),
                Forms\Components\Section::make('Relacionamentos')
                    ->schema([
                        Forms\Components\Select::make('referrer_id')
                            ->label('Indicador')
                            ->relationship(
                                name: 'referrer',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->where('company_id', $companyId);
                                    }
                                }
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship(
                                name: 'client',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->where('company_id', $companyId);
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('responsible_user_id')
                            ->label('Responsável interno')
                            ->relationship(
                                name: 'responsible',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->whereHas('companies', fn ($relationQuery) => $relationQuery->where('company_id', $companyId));
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('invoice_id')
                            ->label('Fatura vinculada')
                            ->relationship(
                                name: 'invoice',
                                titleAttribute: 'invoice_number',
                                modifyQueryUsing: static function (Builder $query) use ($companyId) {
                                    if ($companyId) {
                                        $query->where('company_id', $companyId);
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Indicador')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Valor estimado')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aberto',
                        'warning' => 'em_andamento',
                        'danger' => 'encerrado',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Abertura')
                    ->date()
                    ->sortable(),
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
            'index' => Pages\ListReferralCases::route('/'),
            'create' => Pages\CreateReferralCase::route('/create'),
            'edit' => Pages\EditReferralCase::route('/{record}/edit'),
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
