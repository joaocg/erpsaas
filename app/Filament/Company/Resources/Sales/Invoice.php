<?php

namespace App\Filament\Company\Resources\Sales;

use App\Models\Accounting\Invoice as InvoiceModel;

/**
 * Alias for InvoiceResource to satisfy callers referencing the legacy class name.
 */
class Invoice extends InvoiceResource
{
    public static function find($id, $columns = ['*'])
    {
        return InvoiceModel::find($id, $columns);
    }
}
