<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\RelationManagers;

use App\Models\Accounting\Transaction;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ClientExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'clientExpensesPlaceholder';

    public function form(Form $form): Form
    {
        return $form;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->whereRaw('0 = 1'))
            ->columns([]);
    }
}
