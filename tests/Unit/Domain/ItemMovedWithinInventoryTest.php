<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemMovedWithinInventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory ID and slot indexes', function (): void {
    $inventoryId = new InventoryId('inventory-123');

    $event = new ItemMovedWithinInventory(
        inventoryId: $inventoryId,
        sourceSlotIndex: 2,
        destinationSlotIndex: 5,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->sourceSlotIndex)->toBe(2)
        ->and($event->destinationSlotIndex)->toBe(5);
});

it('implements the domain event contract', function (): void {
    $event = new ItemMovedWithinInventory(
        inventoryId: new InventoryId('inventory-123'),
        sourceSlotIndex: 0,
        destinationSlotIndex: 1,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
