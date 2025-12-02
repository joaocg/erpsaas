<?php

namespace App\Filament\Company\Resources\Sales;

use App\Enums\CommissionStatus;
use App\Filament\Company\Resources\Sales\CommissionResource\Pages;
use App\Models\Company;
use App\Models\Commission;
use App\Models\Partner;
use App\Services\CommissionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $tenantModel = Company::class;

    protected static ?string $tenantRelationshipName = 'commissions';

    public static function getNavigationLabel(): string
    {
        return __('Commissions');
    }

    public static function getModelLabel(): string
    {
        return __('Commission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Commissions');
    }

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Commission Details'))
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label(__('Partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (! $state) {
                                    return;
                                }

                                $partner = Partner::find($state);

                                if (! $partner) {
                                    return;
                                }

                                $set('commission_percent', $partner->commission_percent);

                                static::updateCommissionAmount($set, $get);
                            })
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label(__('Client'))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('invoice_id')
                            ->label(__('Invoice'))
                            ->relationship('invoice', 'invoice_number')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (! $state) {
                                    return;
                                }

                                $invoice = Invoice::find($state);

                                if (! $invoice) {
                                    return;
                                }

                                $set('base_amount', $invoice->amount_paid ?? $invoice->total);

                                static::updateCommissionAmount($set, $get);
                            })
                            ->required(),
                        Forms\Components\Select::make('bill_id')
                            ->label(__('Bill'))
                            ->relationship('bill', 'bill_number')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('base_amount')
                            ->label(__('Base amount'))
                            ->numeric()
                            ->step('0.01')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::updateCommissionAmount($set, $get);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('commission_percent')
                            ->label(__('Commission %'))
                            ->numeric()
                            ->step('0.01')
                            ->suffix('%')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::updateCommissionAmount($set, $get);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label(__('Commission amount'))
                            ->numeric()
                            ->step('0.01')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options(CommissionStatus::class)
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label(__('Due date'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label(__('Paid at'))
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('Partner'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(__('Client'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label(__('Invoice'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_amount')
                    ->label(__('Base'))
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) : null)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commission_percent')
                    ->label(__('Commission %'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2) . '%' : null)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label(__('Commission amount'))
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) : null)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('Due date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Paid at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('partner_id')
                    ->label(__('Partner'))
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(CommissionStatus::class)
                    ->multiple(),
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('generate_bill')
                    ->label(__('Generate Bill'))
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->visible(fn (Commission $record) => $record->invoice && ! $record->bill_id && $record->status !== CommissionStatus::Canceled)
                    ->action(function (Commission $record) {
                        app(CommissionService::class)->accrueForPaidInvoice(
                            $record->invoice,
                            $record->invoice?->paid_at ?? Carbon::now()
                        );
                    }),
                Tables\Actions\Action::make('mark_paid')
                    ->label(__('Mark as Paid'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Commission $record) => $record->status !== CommissionStatus::Paid)
                    ->action(function (Commission $record) {
                        $record->update([
                            'status' => CommissionStatus::Paid,
                            'paid_at' => $record->paid_at ?? Carbon::now(),
                        ]);
                    }),
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
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }

    protected static function updateCommissionAmount(Set $set, Get $get): void
    {
        $baseAmount = (float) ($get('base_amount') ?? 0);
        $percent = $get('commission_percent');

        if ($percent === null || $percent === '') {
            $set('commission_amount', null);

            return;
        }

        $set('commission_amount', static::calculateCommissionAmount($baseAmount, (float) $percent));
    }

    protected static function calculateCommissionAmount(float $baseAmount, float $percent): float
    {
        return round($baseAmount * ($percent / 100), 2);
    }
}
