
<?php

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\Entities\InventorySlot;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use InvalidArgumentException;

it('reconstitutes an inventory with its persisted identity, owner, capacity and version', function (): void {
    $inventoryId = new InventoryId('inventory-123');
    $characterId = new CharacterId('character-123');

    $slots = [
        new InventorySlot(0, null),
        new InventorySlot(1, null),
        new InventorySlot(2, null),
    ];

    $inventory = Inventory::reconstitute(
        $inventoryId,
        $characterId,
        7,
        $slots,
    );

    expect($inventory->id())->toBe($inventoryId)
        ->and($inventory->characterId())->toBe($characterId)
        ->and($inventory->capacity())->toBe(3)
        ->and($inventory->version())->toBe(7)
        ->and($inventory->slots())->toHaveCount(3);
});

it('restores empty slots, item stacks and unique item instances', function (): void {
    $stack = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(50),
        new StackCompatibilityKey('standard'),
    );

    $itemInstanceId = new ItemInstanceId('sword-instance-123');

    $slots = [
        new InventorySlot(0, $stack),
        new InventorySlot(1, null),
        new InventorySlot(2, $itemInstanceId),
    ];

    $inventory = Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        12,
        $slots,
    );

    expect($inventory->slotAt(0)?->stack())->toBe($stack)
        ->and($inventory->slotAt(1)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(2)?->itemInstanceId())->toBe($itemInstanceId);
});

it('does not record domain events when reconstituting an inventory', function (): void {
    $inventory = Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5,
        [
            new InventorySlot(0, null),
            new InventorySlot(1, new ItemInstanceId('item-instance-123')),
        ],
    );

    expect($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(5);
});

it('rejects a negative persisted version', function (): void {
    Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        -1,
        [new InventorySlot(0, null)],
    );
})->throws(InvalidArgumentException::class);

it('rejects an inventory with no persisted slots', function (): void {
    Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        0,
        [],
    );
})->throws(InvalidArgumentException::class);

it('rejects persisted slots with non-contiguous indexes', function (): void {
    Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        0,
        [
            new InventorySlot(0, null),
            new InventorySlot(2, null),
        ],
    );
})->throws(InvalidArgumentException::class);

it('rejects a non-list array of persisted slots', function (): void {
    Inventory::reconstitute(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        0,
        [
            1 => new InventorySlot(1, null),
        ],
    );
})->throws(InvalidArgumentException::class);
