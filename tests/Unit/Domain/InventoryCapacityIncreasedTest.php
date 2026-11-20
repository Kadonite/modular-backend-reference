<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\InventoryCapacityIncreased;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory capacity increase details', function () {
    $inventoryId = new InventoryId('inventory-1');

    $event = new InventoryCapacityIncreased(
        $inventoryId,
        10,
        15,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->previousCapacity)->toBe(10)
        ->and($event->newCapacity)->toBe(15);
});

it('implements the shared domain event contract', function () {
    $event = new InventoryCapacityIncreased(
        new InventoryId('inventory-1'),
        10,
        15,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
