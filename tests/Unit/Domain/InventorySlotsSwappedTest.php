<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\InventorySlotsSwapped;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory ID and slot indexes', function (): void {
    $inventoryId = new InventoryId('inventory-123');

    $event = new InventorySlotsSwapped(
        inventoryId: $inventoryId,
        firstSlotIndex: 1,
        secondSlotIndex: 4,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->firstSlotIndex)->toBe(1)
        ->and($event->secondSlotIndex)->toBe(4);
});

it('implements the domain event contract', function (): void {
    $event = new InventorySlotsSwapped(
        inventoryId: new InventoryId('inventory-123'),
        firstSlotIndex: 0,
        secondSlotIndex: 1,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
