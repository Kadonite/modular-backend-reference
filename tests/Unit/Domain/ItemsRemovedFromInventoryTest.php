<?php

declare(strict_types=1);

use App\Modules\Inventory\Domain\Events\ItemsRemovedFromInventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use App\Shared\Domain\DomainEvent;

it('stores the inventory and item details', function (): void {
    $inventoryId = new InventoryId('inventory-123');
    $itemDefinitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $event = new ItemsRemovedFromInventory(
        inventoryId: $inventoryId,
        itemDefinitionId: $itemDefinitionId,
        compatibilityKey: $compatibilityKey,
        quantity: 50,
        slotIndexes: [0, 1],
    );

    expect($event->inventoryId)->toBe($inventoryId)
        ->and($event->itemDefinitionId)->toBe($itemDefinitionId)
        ->and($event->compatibilityKey)->toBe($compatibilityKey)
        ->and($event->quantity)->toBe(50)
        ->and($event->slotIndexes)->toBe([0, 1]);
});

it('implements the domain event contract', function (): void {
    $event = new ItemsRemovedFromInventory(
        inventoryId: new InventoryId('inventory-123'),
        itemDefinitionId: new ItemDefinitionId('iron-ore'),
        compatibilityKey: new StackCompatibilityKey('standard'),
        quantity: 10,
        slotIndexes: [0],
    );

    expect($event)->toBeInstanceOf(DomainEvent::class);
});
