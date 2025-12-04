<?php

namespace App\Observers;

use App\Models\ServiceActivity;

class ServiceActivityObserver
{
    public function saved(ServiceActivity $serviceActivity): void
    {
        $serviceActivity->service?->refreshTotals();
    }

    public function deleted(ServiceActivity $serviceActivity): void
    {
        $serviceActivity->service?->refreshTotals();
    }
}
