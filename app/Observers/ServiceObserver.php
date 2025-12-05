<?php

namespace App\Observers;

use App\Models\Service;

class ServiceObserver
{
    public function saved(Service $service): void
    {
        $service->refreshTotals();
    }
}
