<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemStackSplit;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory ID, slot indexes and split quantity', function (): void {
    $inventoryId = new InventoryId('inventory-123');

    $event = new ItemStackSplit(
        inventoryId: $inventoryId,
        sourceSlotIndex: 2,
        destinationSlotIndex: 5,
        quantitySplit: 10,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->sourceSlotIndex)->toBe(2)
        ->and($event->destinationSlotIndex)->toBe(5)
        ->and($event->quantitySplit)->toBe(10);
});

it('implements the domain event contract', function (): void {
    $event = new ItemStackSplit(
        inventoryId: new InventoryId('inventory-123'),
        sourceSlotIndex: 0,
        destinationSlotIndex: 1,
        quantitySplit: 5,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
