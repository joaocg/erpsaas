<?php

namespace App\Filament\Company\Resources\Common;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Common\OfferingType;
use App\Filament\Company\Resources\Common\OfferingResource\Pages;
use App\Filament\Forms\Components\Banner;
use App\Filament\Forms\Components\CreateAccountSelect;
use App\Filament\Forms\Components\CreateAdjustmentSelect;
use App\Models\Common\Offering;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

class OfferingResource extends Resource
{
    protected static ?string $model = Offering::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    public static function getNavigationLabel(): string
    {
        return __('Offerings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Offerings');
    }

    public static function getModelLabel(): string
    {
        return __('Offering');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Banner::make('inactiveAdjustments')
                    ->label(__('Inactive adjustments'))
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn (?Offering $record) => $record?->hasInactiveAdjustments())
                    ->columnSpanFull()
                    ->description(function (Offering $record) {
                        $inactiveAdjustments = collect();

                        foreach ($record->adjustments as $adjustment) {
                            if ($adjustment->isInactive() && $inactiveAdjustments->doesntContain($adjustment->name)) {
                                $inactiveAdjustments->push($adjustment->name);
                            }
                        }

                        $adjustmentsList = $inactiveAdjustments->map(static function ($name) {
                            return "<span class='font-medium'>{$name}</span>";
                        })->join(', ');

                        $output = __('<p class="text-sm">This offering contains inactive adjustments that need to be addressed: :adjustments</p>', [
                            'adjustments' => $adjustmentsList,
                        ]);

                        return new HtmlString($output);
                    }),
                static::getGeneralSection(),
                // Sellable Section
                static::getSellableSection(),
                // Purchasable Section
                static::getPurchasableSection(),
            ])->columns();
    }

    public static function getGeneralSection(bool $hasAttributeChoices = true): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('General'))
            ->schema([
                RadioDeck::make('type')
                    ->options(OfferingType::class)
                    ->default(OfferingType::Product)
                    ->icons(OfferingType::class)
                    ->color('primary')
                    ->columns()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->autofocus()
                    ->required()
                    ->columnStart(1)
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label(__('Price'))
                    ->required()
                    ->money(),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->columnSpan(2)
                    ->rows(3),
                Forms\Components\CheckboxList::make('attributes')
                    ->options([
                        'Sellable' => __('Sellable'),
                        'Purchasable' => __('Purchasable'),
                    ])
                    ->visible($hasAttributeChoices)
                    ->hiddenLabel()
                    ->required()
                    ->live()
                    ->bulkToggleable()
                    ->validationMessages([
                        'required' => __('The offering must be either sellable or purchasable.'),
                    ]),
            ])->columns();
    }

    public static function getSellableSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Sale Information'))
            ->schema([
                CreateAccountSelect::make('income_account_id')
                    ->label(__('Income account'))
                    ->category(AccountCategory::Revenue)
                    ->type(AccountType::OperatingRevenue)
                    ->required()
                    ->validationMessages([
                        'required' => __('The income account is required for sellable offerings.'),
                    ]),
                CreateAdjustmentSelect::make('salesTaxes')
                    ->label(__('Sales tax'))
                    ->category(AdjustmentCategory::Tax)
                    ->type(AdjustmentType::Sales)
                    ->multiple(),
                CreateAdjustmentSelect::make('salesDiscounts')
                    ->label(__('Sales discount'))
                    ->category(AdjustmentCategory::Discount)
                    ->type(AdjustmentType::Sales)
                    ->multiple(),
            ])
            ->columns()
            ->visible(static fn (Forms\Get $get) => in_array('Sellable', $get('attributes') ?? []));
    }

    public static function getPurchasableSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Purchase Information'))
            ->schema([
                CreateAccountSelect::make('expense_account_id')
                    ->label(__('Expense account'))
                    ->category(AccountCategory::Expense)
                    ->type(AccountType::OperatingExpense)
                    ->required()
                    ->validationMessages([
                        'required' => __('The expense account is required for purchasable offerings.'),
                    ]),
                CreateAdjustmentSelect::make('purchaseTaxes')
                    ->label(__('Purchase tax'))
                    ->category(AdjustmentCategory::Tax)
                    ->type(AdjustmentType::Purchase)
                    ->multiple(),
                CreateAdjustmentSelect::make('purchaseDiscounts')
                    ->label(__('Purchase discount'))
                    ->category(AdjustmentCategory::Discount)
                    ->type(AdjustmentType::Purchase)
                    ->multiple(),
            ])
            ->columns()
            ->visible(static fn (Forms\Get $get) => in_array('Purchasable', $get('attributes') ?? []));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->selectRaw("
                        *,
                        CONCAT_WS(' & ',
                            CASE WHEN sellable THEN 'Sellable' END,
                            CASE WHEN purchasable THEN 'Purchasable' END
                        ) AS attributes
                    ");
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('attributes')
                    ->label(__('Attributes'))
                    ->formatStateUsing(function (?string $state) {
                        $attributes = collect(preg_split('/\s*&\s*/', $state ?? '', -1, PREG_SPLIT_NO_EMPTY));

                        return $attributes
                            ->map(function (string $attribute) {
                                return match ($attribute) {
                                    'Sellable' => __('Sellable'),
                                    'Purchasable' => __('Purchasable'),
                                    default => $attribute,
                                };
                            })
                            ->implode(', ');
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->currency()
                    ->sortable()
                    ->description(function (Offering $record) {
                        $adjustments = $record->adjustments()
                            ->pluck('name')
                            ->join(', ');

                        if (empty($adjustments)) {
                            return null;
                        }

                        $adjustmentsList = Str::of($adjustments)->limit(40);

                        return __(
                            '+ :adjustments',
                            ['adjustments' => $adjustmentsList],
                        );
                    }),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfferings::route('/'),
            'create' => Pages\CreateOffering::route('/create'),
            'edit' => Pages\EditOffering::route('/{record}/edit'),
        ];
    }
}
