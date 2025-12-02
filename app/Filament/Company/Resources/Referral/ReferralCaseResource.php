<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferralCaseResource\Pages;
use App\Models\Referral\ReferralCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralCaseResource extends Resource
{
    protected static ?string $model = ReferralCase::class;

    protected static ?string $navigationGroup = 'Comissionamentos';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function getNavigationLabel(): string
    {
        return __('Casos indicados');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('referrer_id')
                ->label(__('Indicador'))
                ->relationship('referrer', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('client_id')
                ->label(__('Cliente'))
                ->relationship('client', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('invoice_id')
                ->label(__('Fatura (Receitas)'))
                ->relationship('invoice', 'invoice_number')
                ->searchable()
                ->preload()
                ->helperText(__('Relacione a indicação com uma fatura existente em Vendas para integrar aos relatórios.')),
            Forms\Components\Select::make('office_lawyer_id')
                ->label(__('Advogado responsável'))
                ->relationship('officeLawyer', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('description')
                ->label(__('Descrição'))
                ->required(),
            Forms\Components\TextInput::make('case_value')
                ->label(__('Valor do caso'))
                ->numeric()
                ->required()
                ->prefix('R$'),
            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'pending' => __('Pendente'),
                    'in_progress' => __('Em andamento'),
                    'won' => __('Ganho'),
                    'lost' => __('Perdido'),
                    'cancelled' => __('Cancelado'),
                ])->default('pending'),
            Forms\Components\DatePicker::make('contract_date')->label(__('Data do contrato')),
            Forms\Components\DatePicker::make('expected_payment_date')->label(__('Previsto para pagamento')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')->label(__('Indicador'))->searchable(),
                Tables\Columns\TextColumn::make('client.name')->label(__('Cliente'))->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label(__('Fatura'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('case_value')->label(__('Valor'))->money('BRL')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label(__('Status')),
                Tables\Columns\TextColumn::make('expected_payment_date')->label(__('Vencimento'))->date(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Criado em'))->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pendente'),
                        'in_progress' => __('Em andamento'),
                        'won' => __('Ganho'),
                        'lost' => __('Perdido'),
                        'cancelled' => __('Cancelado'),
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
            'index' => Pages\ListReferralCases::route('/'),
            'create' => Pages\CreateReferralCase::route('/create'),
            'edit' => Pages\EditReferralCase::route('/{record}/edit'),
        ];
    }
}
