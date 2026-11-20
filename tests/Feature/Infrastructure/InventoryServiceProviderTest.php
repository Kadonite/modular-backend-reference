<?php

use App\Modules\Inventory\Application\Contracts\InventoryRepository;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryRepository;

it('resolves the inventory repository contract to its Eloquent implementation', function (): void {
    $repository = app(InventoryRepository::class);

    expect($repository)->toBeInstanceOf(EloquentInventoryRepository::class);
});
