<?php

namespace App\Filament\Company\Resources\Referral;

use App\Filament\Company\Resources\Referral\ReferralCommissionResource\Pages;
use App\Models\Referral\ReferralCommission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static ?string $navigationGroup = 'Comissionamentos';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('Comissões de Indicação');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('referral_case_id')
                ->label(__('Caso'))
                ->relationship('referralCase', 'description')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('referrer_id')
                ->label(__('Indicador'))
                ->relationship('referrer', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('bill_id')
                ->label(__('Conta a pagar (Bill)'))
                ->relationship('bill', 'bill_number')
                ->searchable()
                ->preload()
                ->helperText(__('Associe a comissão a uma conta a pagar existente para refletir em Compras e Transações.')),
            Forms\Components\Select::make('transaction_id')
                ->label(__('Transação contábil'))
                ->relationship('transaction', 'id')
                ->searchable()
                ->preload()
                ->helperText(__('Vincule a um lançamento em /accounting/transactions quando já houver pagamento registrado.')),
            Forms\Components\TextInput::make('commission_percentage')
                ->label(__('Percentual'))
                ->numeric()
                ->step(0.01),
            Forms\Components\TextInput::make('commission_value')
                ->label(__('Valor'))
                ->numeric()
                ->prefix('R$'),
            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'pending' => __('Pendente'),
                    'scheduled' => __('Agendado'),
                    'paid' => __('Pago'),
                    'cancelled' => __('Cancelado'),
                ])->default('pending'),
            Forms\Components\DatePicker::make('due_date')->label(__('Vencimento')),
            Forms\Components\DatePicker::make('payment_date')->label(__('Pagamento')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referralCase.description')->label(__('Caso')),
                Tables\Columns\TextColumn::make('referrer.name')->label(__('Indicador'))->searchable(),
                Tables\Columns\TextColumn::make('bill.bill_number')
                    ->label(__('Bill'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction.id')
                    ->label(__('Transação'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commission_percentage')->label(__('Percentual'))->suffix('%'),
                Tables\Columns\TextColumn::make('commission_value')->label(__('Valor'))->money('BRL')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label(__('Status')),
                Tables\Columns\TextColumn::make('due_date')->label(__('Vencimento'))->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pendente'),
                        'scheduled' => __('Agendado'),
                        'paid' => __('Pago'),
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
            'index' => Pages\ListReferralCommissions::route('/'),
            'create' => Pages\CreateReferralCommission::route('/create'),
            'edit' => Pages\EditReferralCommission::route('/{record}/edit'),
        ];
    }
}
