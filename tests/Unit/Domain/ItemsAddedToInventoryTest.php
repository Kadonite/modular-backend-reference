<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemsAddedToInventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use App\Shared\Domain\DomainEvent;

it('stores the inventory and item details', function (): void {
    $inventoryId = new InventoryId('inventory-123');
    $itemDefinitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $event = new ItemsAddedToInventory(
        inventoryId: $inventoryId,
        itemDefinitionId: $itemDefinitionId,
        compatibilityKey: $compatibilityKey,
        quantity: 150,
        slotIndexes: [0, 1, 2],
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->itemDefinitionId)->toBe($itemDefinitionId)
        ->and($event->compatibilityKey)->toBe($compatibilityKey)
        ->and($event->quantity)->toBe(150)
        ->and($event->slotIndexes)->toBe([0, 1, 2]);
});

it('implements the domain event contract', function (): void {
    $event = new ItemsAddedToInventory(
        inventoryId: new InventoryId('inventory-123'),
        itemDefinitionId: new ItemDefinitionId('iron-ore'),
        compatibilityKey: new StackCompatibilityKey('standard'),
        quantity: 10,
        slotIndexes: [0],
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
