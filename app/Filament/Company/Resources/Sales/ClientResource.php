<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ClientResource\Pages;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\ClientExpensesRelationManager;
use App\Filament\Exports\Common\ClientExporter;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\PhoneBuilder;
use App\Filament\Tables\Columns;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\ClientExpensesRelationManager;
use App\Models\Common\Address;
use App\Models\Common\Client;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    public static function getNavigationLabel(): string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Clients');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('General Information'))
                    ->schema([
                        Forms\Components\Group::make()
                            ->columns()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Client name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('account_number')
                                    ->label(__('Account number'))
                                    ->maxLength(255)
                                    ->columnStart(1),
                                Forms\Components\TextInput::make('website')
                                    ->label(__('Website'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Notes'))
                                    ->columnSpanFull(),
                            ]),
                        CustomSection::make(__('Primary Contact'))
                            ->relationship('primaryContact')
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->contained(false)
                            ->schema([
                                Forms\Components\Hidden::make('is_primary')
                                    ->default(true),
                                Forms\Components\TextInput::make('first_name')
                                    ->label(__('First name'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label(__('Last name'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                PhoneBuilder::make('phones')
                                    ->hiddenLabel()
                                    ->blockLabels(false)
                                    ->default([
                                        ['type' => 'primary'],
                                    ])
                                    ->columnSpanFull()
                                    ->blocks([
                                        Forms\Components\Builder\Block::make('primary')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label(__('Phone'))
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('mobile')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label(__('Mobile'))
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('toll_free')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label(__('Toll free'))
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('fax')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label(__('Fax'))
                                                    ->live()
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                    ])
                                    ->deletable(fn (PhoneBuilder $builder) => $builder->getItemsCount() > 1)
                                    ->reorderable(false)
                                    ->blockNumbers(false)
                                    ->addActionLabel(__('Add Phone')),
                            ])->columns(),
                        Forms\Components\Repeater::make('secondaryContacts')
                            ->relationship()
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->hiddenLabel()
                            ->extraAttributes([
                                'class' => 'uncontained',
                            ])
                            ->columns()
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->itemLabel(function (Forms\Components\Repeater $component, array $state): ?string {
                                if ($component->getItemsCount() === 1) {
                                    return __('Secondary Contact');
                                }

                                $firstName = $state['first_name'] ?? null;
                                $lastName = $state['last_name'] ?? null;

                                if ($firstName && $lastName) {
                                    return "{$firstName} {$lastName}";
                                }

                                if ($firstName) {
                                    return $firstName;
                                }

                                return __('Secondary Contact');
                            })
                            ->addActionLabel(__('Add Contact'))
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label(__('First name'))
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label(__('Last name'))
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->maxLength(255),
                                PhoneBuilder::make('phones')
                                    ->hiddenLabel()
                                    ->blockLabels(false)
                                    ->default([
                                        ['type' => 'primary'],
                                    ])
                                    ->blocks([
                                        Forms\Components\Builder\Block::make('primary')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label(__('Phone'))
                                                    ->maxLength(255),
                                            ])->maxItems(1),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->blockNumbers(false),
                            ]),
                    ])->columns(1),
                Forms\Components\Section::make(__('Billing'))
                    ->schema([
                        CreateCurrencySelect::make('currency_code')
                            ->softRequired(),
                        CustomSection::make(__('Billing Address'))
                            ->relationship('billingAddress')
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->contained(false)
                            ->schema([
                                Forms\Components\Hidden::make('type')
                                    ->default('billing'),
                                AddressFields::make(),
                            ])->columns(),
                    ])
                    ->columns(1),
                Forms\Components\Section::make(__('Shipping'))
                    ->relationship('shippingAddress')
                    ->saveRelationshipsUsing(null)
                    ->saveRelationshipsBeforeChildrenUsing(null)
                    ->dehydrated(true)
                    ->schema([
                        Forms\Components\Hidden::make('type')
                            ->default('shipping'),
                        Forms\Components\TextInput::make('recipient')
                            ->label(__('Recipient'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->maxLength(255),
                        CustomSection::make(__('Shipping Address'))
                            ->contained(false)
                            ->schema([
                                Forms\Components\Checkbox::make('same_as_billing')
                                    ->label(__('Same as billing address'))
                                    ->live()
                                    ->afterStateHydrated(function (?Address $record, Forms\Components\Checkbox $component) {
                                        if (! $record || $record->parent_address_id) {
                                            return $component->state(true);
                                        }

                                        return $component->state(false);
                                    })
                                    ->afterStateUpdated(static function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            return;
                                        }

                                        $billingAddress = $get('../billingAddress');

                                        $fieldsToSync = [
                                            'address_line_1',
                                            'address_line_2',
                                            'country_code',
                                            'state_id',
                                            'city',
                                            'postal_code',
                                        ];

                                        foreach ($fieldsToSync as $field) {
                                            $set($field, $billingAddress[$field]);
                                        }
                                    })
                                    ->columnSpanFull(),
                                AddressFields::make()
                                    ->visible(static fn (Get $get) => ! $get('same_as_billing')),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Delivery instructions'))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])->columns(),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (Client $client) => $client->primaryContact?->full_name),
                Tables\Columns\TextColumn::make('primaryContact.email')
                    ->label(__('Email'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('primaryContact.phones')
                    ->label(__('Phone'))
                    ->toggleable()
                    ->state(static fn (Client $client) => $client->primaryContact?->first_available_phone),
                Tables\Columns\TextColumn::make('billingAddress.address_string')
                    ->label(__('Billing address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('balance')
                    ->label(__('Balance'))
                    ->getStateUsing(function (Client $client) {
                        return $client->invoices()
                            ->unpaid()
                            ->get()
                            ->sumMoneyInDefaultCurrency('amount_due');
                    })
                    ->coloredDescription(function (Client $client) {
                        $overdue = $client->invoices()
                            ->overdue()
                            ->get()
                            ->sumMoneyInDefaultCurrency('amount_due');

                        if ($overdue <= 0) {
                            return null;
                        }

                        $formattedOverdue = CurrencyConverter::formatCentsToMoney($overdue);

                        return __('Overdue: :amount', ['amount' => $formattedOverdue]);
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query
                            ->withSum(['invoices' => fn (Builder $query) => $query->unpaid()], 'amount_due')
                            ->orderBy('invoices_sum_amount_due', $direction);
                    })
                    ->currency(convert: false)
                    ->alignEnd(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(ClientExporter::class),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\ViewAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ClientResource\RelationManagers\ClientExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
