<?php

namespace App\Providers;

use App\Filters\Api\Clock\DateFilter;
use App\Filters\Api\Clock\DepartmentFilter;
use App\Filters\Api\Clock\MonthFilter;
use App\Services\Api\AuthorizationService;
use App\Services\Api\Clock\ClockExportService;
use App\Services\Api\Clock\ClockService;
use Illuminate\Support\ServiceProvider;

class ClockServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ClockService::class, function ($app) {
            return new ClockService(
                $app->make(AuthorizationService::class),
                $app->make(ClockExportService::class),
                [
                    $app->make(DepartmentFilter::class),
                    $app->make(DateFilter::class),
                    $app->make(MonthFilter::class),
                ]
            );
        });
    }
}