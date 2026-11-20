<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure;

use App\Modules\Inventory\Application\Contracts\InventoryRepository;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryRepository;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryRepository::class,
            EloquentInventoryRepository::class,
        );
    }
}
