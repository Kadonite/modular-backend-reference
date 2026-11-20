<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemInstanceAddedToInventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory ID, item instance ID and slot index', function (): void {
    $inventoryId = new InventoryId('inventory-123');
    $itemInstanceId = new ItemInstanceId('item-instance-456');

    $event = new ItemInstanceAddedToInventory(
        inventoryId: $inventoryId,
        itemInstanceId: $itemInstanceId,
        slotIndex: 3,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->itemInstanceId)->toBe($itemInstanceId)
        ->and($event->slotIndex)->toBe(3);
});

it('implements the domain event contract', function (): void {
    $event = new ItemInstanceAddedToInventory(
        inventoryId: new InventoryId('inventory-123'),
        itemInstanceId: new ItemInstanceId('item-instance-456'),
        slotIndex: 0,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
