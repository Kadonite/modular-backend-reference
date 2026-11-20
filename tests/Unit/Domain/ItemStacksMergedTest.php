<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemStacksMerged;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

it('stores the inventory ID, slot indexes and merged quantity', function (): void {
    $inventoryId = new InventoryId('inventory-123');

    $event = new ItemStacksMerged(
        inventoryId: $inventoryId,
        sourceSlotIndex: 2,
        destinationSlotIndex: 4,
        quantityMerged: 10,
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->sourceSlotIndex)->toBe(2)
        ->and($event->destinationSlotIndex)->toBe(4)
        ->and($event->quantityMerged)->toBe(10);
});

it('implements the domain event contract', function (): void {
    $event = new ItemStacksMerged(
        inventoryId: new InventoryId('inventory-123'),
        sourceSlotIndex: 0,
        destinationSlotIndex: 1,
        quantityMerged: 25,
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
