<?php

namespace App\Filament\Company\Pages;

use Wallo\FilamentCompanies\Pages\Company\CompanySettings;

class ManageCompany extends CompanySettings
{
    public static function getLabel(): string
    {
        return __('Manage Company');
    }

    public static function getSlug(): string
    {
        return 'manage-company';
    }
}
