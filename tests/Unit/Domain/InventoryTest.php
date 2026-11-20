<?php

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\Enums\InventoryError;
use App\Modules\Inventory\Domain\Events\InventoryCapacityIncreased;
use App\Modules\Inventory\Domain\Events\InventorySlotsSwapped;
use App\Modules\Inventory\Domain\Events\ItemInstanceAddedToInventory;
use App\Modules\Inventory\Domain\Events\ItemInstanceRemovedFromInventory;
use App\Modules\Inventory\Domain\Events\ItemMovedWithinInventory;
use App\Modules\Inventory\Domain\Events\ItemsAddedToInventory;
use App\Modules\Inventory\Domain\Events\ItemsRemovedFromInventory;
use App\Modules\Inventory\Domain\Events\ItemStacksMerged;
use App\Modules\Inventory\Domain\Events\ItemStackSplit;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\ItemStackingProfile;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use InvalidArgumentException;

it('creates an inventory with the supplied identity and owner', function (): void {
    $inventoryId = new InventoryId('inventory-123');
    $characterId = new CharacterId('character-123');

    $inventory = new Inventory(
        $inventoryId,
        $characterId,
        10
    );

    expect($inventory->id())->toBe($inventoryId)
        ->and($inventory->characterId())->toBe($characterId);
});

it('creates the requested number of slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        10
    );

    expect($inventory->capacity())->toBe(10)
        ->and($inventory->slots())->toHaveCount(10);
});

it('initialises all slots as empty with contiguous indexes', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    expect(array_map(
        fn ($slot) => $slot->index(),
        $inventory->slots()
    ))->toBe([0, 1, 2, 3, 4]);

    foreach ($inventory->slots() as $slot) {
        expect($slot->isEmpty())->toBeTrue();
    }
});

it('returns a slot by its index', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    expect($inventory->slotAt(2)?->index())->toBe(2);
});

it('returns null for an index outside its capacity', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    expect($inventory->slotAt(5))->toBeNull()
        ->and($inventory->slotAt(-1))->toBeNull();
});

it('starts with version zero', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    expect($inventory->version())->toBe(0);
});

it('rejects zero capacity', function (): void {
    new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        0
    );
})->throws(InvalidArgumentException::class);

it('rejects negative capacity', function (): void {
    new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        -1
    );
})->throws(InvalidArgumentException::class);

it('increases inventory capacity by appending empty slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $inventory->increaseCapacity(3);

    expect($inventory->capacity())->toBe(8)
        ->and(array_map(
            fn ($slot) => $slot->index(),
            $inventory->slots()
        ))->toBe([0, 1, 2, 3, 4, 5, 6, 7]);

    foreach (array_slice($inventory->slots(), 5) as $slot) {
        expect($slot->isEmpty())->toBeTrue();
    }
});

it('preserves existing slot objects when increasing capacity', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $originalSlots = $inventory->slots();

    $inventory->increaseCapacity(3);

    foreach ($originalSlots as $index => $originalSlot) {
        expect($inventory->slotAt($index))->toBe($originalSlot);
    }
});

it('increments the inventory version once per successful capacity increase', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $inventory->increaseCapacity(4);

    expect($inventory->version())->toBe(1);
});

it('rejects zero capacity increases without changing inventory state', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $originalSlots = $inventory->slots();

    expect(fn () => $inventory->increaseCapacity(0))
        ->toThrow(InvalidArgumentException::class);

    expect($inventory->capacity())->toBe(5)
        ->and($inventory->version())->toBe(0);

    foreach ($originalSlots as $index => $originalSlot) {
        expect($inventory->slotAt($index))->toBe($originalSlot);
    }
});

it('rejects negative capacity increases without changing inventory state', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    expect(fn () => $inventory->increaseCapacity(-2))
        ->toThrow(InvalidArgumentException::class);

    expect($inventory->capacity())->toBe(5)
        ->and($inventory->version())->toBe(0);
});

it('adds an item instance to the first available slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $itemInstanceId = new ItemInstanceId('instance-123');

    $result = $inventory->addItemInstance($itemInstanceId);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe(0)
        ->and($inventory->slotAt(0)?->itemInstanceId())
        ->toBe($itemInstanceId)
        ->and($inventory->version())->toBe(1);
});

it('adds successive item instances to successive empty slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $first = $inventory->addItemInstance(
        new ItemInstanceId('instance-123')
    );

    $second = $inventory->addItemInstance(
        new ItemInstanceId('instance-456')
    );

    expect($first->value())->toBe(0)
        ->and($second->value())->toBe(1)
        ->and($inventory->version())->toBe(2);
});

it('rejects an item instance already present in the inventory', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $inventory->addItemInstance(
        new ItemInstanceId('instance-123')
    );

    $originalSlot = $inventory->slotAt(0);

    $result = $inventory->addItemInstance(
        new ItemInstanceId('instance-123')
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())
        ->toBe(InventoryError::ItemInstanceAlreadyPresent)
        ->and($inventory->slotAt(0))->toBe($originalSlot)
        ->and($inventory->version())->toBe(1);
});

it('rejects adding an item instance when inventory is full', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        1
    );

    $inventory->addItemInstance(
        new ItemInstanceId('instance-123')
    );

    $originalSlot = $inventory->slotAt(0);

    $result = $inventory->addItemInstance(
        new ItemInstanceId('instance-456')
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())
        ->toBe(InventoryError::InventoryFull)
        ->and($inventory->capacity())->toBe(1)
        ->and($inventory->slotAt(0))->toBe($originalSlot)
        ->and($inventory->version())->toBe(1);
});

it('adds a stack to the first available slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $result = $inventory->addItem(new ItemQuantity(50), $profile);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->version())->toBe(1);
});

it('distributes quantities across empty slots respecting maximum stack size', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $result = $inventory->addItem(new ItemQuantity(250), $profile);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1, 2])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(2)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->version())->toBe(1);
});

it('fills compatible partial stacks before using empty slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(80), $profile);

    $result = $inventory->addItem(new ItemQuantity(150), $profile);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1, 2])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(2)?->stack()?->quantity()->value())->toBe(30)
        ->and($inventory->version())->toBe(2);
});

it('does not merge stacks with different compatibility keys', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $standard = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $enchanted = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('enchanted')
    );

    $inventory->addItem(new ItemQuantity(50), $standard);

    $result = $inventory->addItem(new ItemQuantity(30), $enchanted);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([1])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(30);
});

it('does not merge stacks with different item definitions', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $ironOre = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $copperOre = new ItemStackingProfile(
        new ItemDefinitionId('copper-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(50), $ironOre);

    $result = $inventory->addItem(new ItemQuantity(30), $copperOre);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([1])
        ->and($inventory->slotAt(0)?->stack()?->itemDefinitionId()->value())->toBe('iron-ore')
        ->and($inventory->slotAt(1)?->stack()?->itemDefinitionId()->value())->toBe('copper-ore');
});

it('rejects additions that exceed available capacity without changing inventory state', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        1
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(90), $profile);

    $originalSlot = $inventory->slotAt(0);
    $originalVersion = $inventory->version();

    $result = $inventory->addItem(new ItemQuantity(20), $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InsufficientCapacity)
        ->and($inventory->slotAt(0))->toBe($originalSlot)
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(90)
        ->and($inventory->version())->toBe($originalVersion);
});

it('rejects adding non-stackable items through the stack operation', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-sword'),
        1,
        new StackCompatibilityKey('unique')
    );

    $result = $inventory->addItem(new ItemQuantity(1), $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::ItemNotStackable)
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slots())->toHaveCount(5);
});

it('removes a quantity from an existing stack', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(80), $profile);

    $result = $inventory->removeItem(
        new ItemDefinitionId('iron-ore'),
        new StackCompatibilityKey('standard'),
        new ItemQuantity(30)
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->version())->toBe(2);
});

it('empties a slot when its entire stack is removed', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        2
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(50), $profile);

    $result = $inventory->removeItem(
        new ItemDefinitionId('iron-ore'),
        new StackCompatibilityKey('standard'),
        new ItemQuantity(50)
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0])
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->capacity())->toBe(2)
        ->and($inventory->version())->toBe(2);
});

it('removes matching quantities across multiple stacks in slot order', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        50,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(120), $profile);

    $result = $inventory->removeItem(
        new ItemDefinitionId('iron-ore'),
        new StackCompatibilityKey('standard'),
        new ItemQuantity(70)
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1])
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(30)
        ->and($inventory->slotAt(2)?->stack()?->quantity()->value())->toBe(20)
        ->and($inventory->version())->toBe(2);
});

it('does not remove stacks with a different compatibility key', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(50), $profile);

    $result = $inventory->removeItem(
        new ItemDefinitionId('iron-ore'),
        new StackCompatibilityKey('enchanted'),
        new ItemQuantity(20)
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InsufficientQuantity)
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->version())->toBe(1);
});

it('rejects insufficient removal quantity without changing inventory state', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(80), $profile);

    $originalSlots = $inventory->slots();
    $originalVersion = $inventory->version();

    $result = $inventory->removeItem(
        new ItemDefinitionId('iron-ore'),
        new StackCompatibilityKey('standard'),
        new ItemQuantity(100)
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InsufficientQuantity)
        ->and($inventory->version())->toBe($originalVersion);

    foreach ($originalSlots as $index => $originalSlot) {
        expect($inventory->slotAt($index))->toBe($originalSlot);
    }
});

it('removes an item instance from its inventory slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-1');

    $inventory->addItemInstance($itemInstanceId);

    $result = $inventory->removeItemInstance($itemInstanceId);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe(0)
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->version())->toBe(2);
});

it('removes the correct item instance when multiple instances exist', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $firstItem = new ItemInstanceId('item-instance-1');
    $secondItem = new ItemInstanceId('item-instance-2');

    $inventory->addItemInstance($firstItem);
    $inventory->addItemInstance($secondItem);

    $result = $inventory->removeItemInstance($secondItem);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe(1)
        ->and($inventory->slotAt(0)?->itemInstanceId()?->equals($firstItem))->toBeTrue()
        ->and($inventory->slotAt(1)?->isEmpty())->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('returns an error when the item instance does not exist', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $result = $inventory->removeItemInstance(
        new ItemInstanceId('missing-item'),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::ItemInstanceNotFound)
        ->and($inventory->version())->toBe(0);
});

it('does not modify inventory state when an item instance is not found', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $existingItem = new ItemInstanceId('item-instance-1');

    $inventory->addItemInstance($existingItem);

    $originalSlots = $inventory->slots();
    $originalVersion = $inventory->version();

    $result = $inventory->removeItemInstance(
        new ItemInstanceId('missing-item'),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($inventory->slots())->toBe($originalSlots)
        ->and($inventory->version())->toBe($originalVersion);
});

it('does not remove a different item instance', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $existingItem = new ItemInstanceId('item-instance-1');

    $inventory->addItemInstance($existingItem);

    $result = $inventory->removeItemInstance(
        new ItemInstanceId('item-instance-2'),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($inventory->slotAt(0)?->itemInstanceId()?->equals($existingItem))->toBeTrue();
});

it('returns zero occupied slots for an empty inventory', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    expect($inventory->occupiedSlots())->toBe(0);
});

it('returns the number of occupied slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));
    $inventory->addItemInstance(new ItemInstanceId('item-2'));

    expect($inventory->occupiedSlots())->toBe(2);
});

it('returns the number of available slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));
    $inventory->addItemInstance(new ItemInstanceId('item-2'));

    expect($inventory->availableSlots())->toBe(3);
});

it('returns zero available slots when inventory is full', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));
    $inventory->addItemInstance(new ItemInstanceId('item-2'));

    expect($inventory->availableSlots())->toBe(0);
});

it('identifies an empty inventory', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    expect($inventory->isEmpty())->toBeTrue();
});

it('identifies a non-empty inventory', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));

    expect($inventory->isEmpty())->toBeFalse();
});

it('does not change the inventory version when querying its state', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        5,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));

    $version = $inventory->version();

    $inventory->occupiedSlots();
    $inventory->availableSlots();
    $inventory->isEmpty();

    expect($inventory->version())->toBe($version);
});

it('moves an item instance into an empty slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $item = new ItemInstanceId('item-1');
    $inventory->addItemInstance($item);

    $originalSourceSlot = $inventory->slotAt(0);
    $originalDestinationSlot = $inventory->slotAt(2);

    $result = $inventory->moveToSlot(0, 2);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 2])
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(2)?->itemInstanceId()?->equals($item))->toBeTrue()
        ->and($inventory->slotAt(0))->not->toBe($originalSourceSlot)
        ->and($inventory->slotAt(2))->not->toBe($originalDestinationSlot)
        ->and($inventory->version())->toBe(2);
});

it('moves an item stack into an empty slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $quantity = new ItemQuantity(10);
    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $profile = new ItemStackingProfile(
        $definitionId,
        100,
        $compatibilityKey,
    );

    $inventory->addItem($quantity, $profile);

    $result = $inventory->moveToSlot(0, 2);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(2)?->stack()?->quantity()->value())->toBe(10)
        ->and($inventory->version())->toBe(2);
});

it('rejects moving from an empty slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $result = $inventory->moveToSlot(0, 2);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SourceSlotEmpty)
        ->and($inventory->version())->toBe(0);
});

it('rejects moving to an occupied slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));
    $inventory->addItemInstance(new ItemInstanceId('item-2'));

    $result = $inventory->moveToSlot(0, 1);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::DestinationSlotOccupied)
        ->and($inventory->version())->toBe(2);
});

it('rejects moving an item to the same slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));

    $result = $inventory->moveToSlot(0, 0);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SameSlot)
        ->and($inventory->version())->toBe(1);
});

it('rejects invalid slot indexes when moving items', function (int $sourceIndex, int $destinationIndex): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $result = $inventory->moveToSlot($sourceIndex, $destinationIndex);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSlotIndex)
        ->and($inventory->version())->toBe(0);
})->with([
    [-1, 1],
    [1, -1],
    [3, 1],
    [1, 3],
]);

it('does not change inventory state when a move fails', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));
    $inventory->addItemInstance(new ItemInstanceId('item-2'));

    $originalSlots = $inventory->slots();
    $originalVersion = $inventory->version();

    $inventory->moveToSlot(0, 1);

    expect($inventory->slots())->toBe($originalSlots)
        ->and($inventory->version())->toBe($originalVersion);
});

it('swaps a stack with a unique item instance', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $uniqueItem = new ItemInstanceId('unique-item-1');

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItemInstance($uniqueItem);
    $inventory->addItem(new ItemQuantity(25), $profile);

    $result = $inventory->swapSlots(0, 1);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1])
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(25)
        ->and($inventory->slotAt(1)?->itemInstanceId()?->equals($uniqueItem))->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('swaps two unique item instances', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $firstItem = new ItemInstanceId('item-1');
    $secondItem = new ItemInstanceId('item-2');

    $inventory->addItemInstance($firstItem);
    $inventory->addItemInstance($secondItem);

    $result = $inventory->swapSlots(0, 1);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->itemInstanceId()?->equals($secondItem))->toBeTrue()
        ->and($inventory->slotAt(1)?->itemInstanceId()?->equals($firstItem))->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('swaps two item stacks', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $firstProfile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $secondProfile = new ItemStackingProfile(
        new ItemDefinitionId('gold-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(25), $firstProfile);
    $inventory->addItem(new ItemQuantity(50), $secondProfile);

    $result = $inventory->swapSlots(0, 1);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->itemDefinitionId()->value())->toBe('gold-ore')
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->slotAt(1)?->stack()?->itemDefinitionId()->value())->toBe('iron-ore')
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(25)
        ->and($inventory->version())->toBe(3);
});

it('moves an occupied first slot into an empty second slot when swapping', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $item = new ItemInstanceId('item-1');
    $inventory->addItemInstance($item);

    $result = $inventory->swapSlots(0, 1);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(1)?->itemInstanceId()?->equals($item))->toBeTrue()
        ->and($inventory->version())->toBe(2);
});

it('moves an occupied second slot into an empty first slot when swapping', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $item = new ItemInstanceId('item-1');
    $inventory->addItemInstance($item);

    // Move the item from slot 0 to slot 1 first.
    $inventory->moveToSlot(0, 1);

    $result = $inventory->swapSlots(1, 0);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->itemInstanceId()?->equals($item))->toBeTrue()
        ->and($inventory->slotAt(1)?->isEmpty())->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('rejects swapping two empty slots', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $result = $inventory->swapSlots(0, 1);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::BothSlotsEmpty)
        ->and($inventory->version())->toBe(0);
});

it('rejects swapping a slot with itself', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));

    $result = $inventory->swapSlots(0, 0);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SameSlot)
        ->and($inventory->version())->toBe(1);
});

it('rejects invalid slot indexes when swapping slots', function (int $firstIndex, int $secondIndex): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $result = $inventory->swapSlots($firstIndex, $secondIndex);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSlotIndex)
        ->and($inventory->version())->toBe(0);
})->with([
    [-1, 1],
    [1, -1],
    [3, 1],
    [1, 3],
]);

it('does not change inventory state when swapping fails', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-1'));

    $originalSlots = $inventory->slots();
    $originalVersion = $inventory->version();

    $result = $inventory->swapSlots(1, 2);

    expect($result->isFailure())->toBeTrue()
        ->and($inventory->slots())->toBe($originalSlots)
        ->and($inventory->version())->toBe($originalVersion);
});

it('merges two compatible stacks completely', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');
    $seedProfile = new ItemStackingProfile(
        $definitionId,
        50,
        $compatibilityKey,
    );

    $mergeProfile = new ItemStackingProfile(
        $definitionId,
        100,
        $compatibilityKey,
    );

    $inventory->addItem(new ItemQuantity(70), $seedProfile);

    expect($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(20);

    $result = $inventory->mergeStacks(1, 0, $mergeProfile);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(70)
        ->and($inventory->slotAt(1)?->isEmpty())->toBeTrue()
        ->and($inventory->version())->toBe(2);
});

it('partially merges stacks and leaves overflow in the source', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');
    $seedProfile = new ItemStackingProfile(
        $definitionId,
        50,
        $compatibilityKey,
    );

    $mergeProfile = new ItemStackingProfile(
        $definitionId,
        60,
        $compatibilityKey,
    );

    $inventory->addItem(new ItemQuantity(80), $seedProfile);

    expect($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(30);

    $result = $inventory->mergeStacks(1, 0, $mergeProfile);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(60)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(20)
        ->and($inventory->version())->toBe(2);
});

it('rejects merging stacks with different compatibility keys', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $definitionId = new ItemDefinitionId('iron-ore');
    $profile = new ItemStackingProfile(
        $definitionId,
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(
        new ItemQuantity(20),
        $profile,
    );

    $inventory->addItem(
        new ItemQuantity(30),
        new ItemStackingProfile(
            $definitionId,
            100,
            new StackCompatibilityKey('enchanted'),
        ),
    );

    $version = $inventory->version();
    $slots = $inventory->slots();

    $result = $inventory->mergeStacks(1, 0, $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::IncompatibleStacks)
        ->and($inventory->slots())->toEqual($slots)
        ->and($inventory->version())->toBe($version);
});

it('rejects merging when the destination stack is full', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(100), $profile);
    $inventory->addItem(new ItemQuantity(20), $profile);

    $version = $inventory->version();
    $slots = $inventory->slots();

    $result = $inventory->mergeStacks(1, 0, $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::DestinationStackFull)
        ->and($inventory->slots())->toEqual($slots)
        ->and($inventory->version())->toBe($version);
});

it('rejects merging when the source slot is empty', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(20), $profile);

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->mergeStacks(1, 0, $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())
        ->toBe(InventoryError::SourceSlotDoesNotContainStack)
        ->and($inventory->slots())->toEqual($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects merging into an empty destination slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(20), $profile);

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->mergeStacks(0, 1, $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())
        ->toBe(InventoryError::DestinationSlotDoesNotContainStack)
        ->and($inventory->slots())->toEqual($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects merging stacks with different item definitions', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $otherProfile = new ItemStackingProfile(
        new ItemDefinitionId('gold-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(20), $profile);
    $inventory->addItem(new ItemQuantity(30), $otherProfile);

    $slots = $inventory->slots();
    $version = $inventory->version();

    $result = $inventory->mergeStacks(1, 0, $profile);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::IncompatibleStacks)
        ->and($inventory->slots())->toEqual($slots)
        ->and($inventory->version())->toBe($version);
});

it('rejects merging when the stacking profile does not match the stacks', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $seedProfile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        50,
        new StackCompatibilityKey('standard'),
    );

    $incorrectProfile = new ItemStackingProfile(
        new ItemDefinitionId('gold-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(70), $seedProfile);

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->mergeStacks(1, 0, $incorrectProfile);
    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::IncompatibleStacks)
        ->and($inventory->slots())->toEqual($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects invalid slot indexes when merging stacks', function (int $sourceIndex, int $destinationIndex): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(20), $profile);
    $inventory->addItem(new ItemQuantity(30), $profile);

    $slots = $inventory->slots();
    $version = $inventory->version();

    $result = $inventory->mergeStacks(
        $sourceIndex,
        $destinationIndex,
        $profile,
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSlotIndex)
        ->and($inventory->slots())->toEqual($slots)
        ->and($inventory->version())->toBe($version);
})->with([
    [-1, 0],
    [0, -1],
    [2, 0],
    [0, 2],
]);

it('splits a stack into an empty destination slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $inventory->addItem(
        new ItemQuantity(80),
        new ItemStackingProfile(
            $definitionId,
            100,
            $compatibilityKey,
        ),
    );

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(30),
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(50)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(30)
        ->and($inventory->version())->toBe(2);
});

it('preserves item definition and compatibility when splitting', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $inventory->addItem(
        new ItemQuantity(80),
        new ItemStackingProfile(
            $definitionId,
            100,
            $compatibilityKey,
        ),
    );

    $inventory->splitStack(0, 1, new ItemQuantity(30));

    $sourceStack = $inventory->slotAt(0)?->stack();
    $destinationStack = $inventory->slotAt(1)?->stack();

    expect($sourceStack?->itemDefinitionId()->equals($definitionId))->toBeTrue()
        ->and($destinationStack?->itemDefinitionId()->equals($definitionId))->toBeTrue()
        ->and($sourceStack?->compatibilityKey()->equals($compatibilityKey))->toBeTrue()
        ->and($destinationStack?->compatibilityKey()->equals($compatibilityKey))->toBeTrue();
});

it('preserves total quantity when splitting a stack', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItem(
        new ItemQuantity(80),
        new ItemStackingProfile(
            new ItemDefinitionId('iron-ore'),
            100,
            new StackCompatibilityKey('standard'),
        ),
    );

    $inventory->splitStack(0, 1, new ItemQuantity(30));

    $totalQuantity =
        $inventory->slotAt(0)?->stack()?->quantity()->value()
        + $inventory->slotAt(1)?->stack()?->quantity()->value();

    expect($totalQuantity)->toBe(80);
});

it('rejects splitting when the destination slot is occupied', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(80), $profile);

    $inventory->splitStack(0, 1, new ItemQuantity(20));

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->splitStack(0, 1, new ItemQuantity(10));

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::DestinationSlotNotEmpty)
        ->and($inventory->slots())->toBe($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects splitting when the source slot is empty', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(10),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SourceSlotDoesNotContainStack)
        ->and($inventory->version())->toBe(0);
});

it('rejects splitting when the source slot contains a unique item instance', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-instance-1'));

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(10),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SourceSlotDoesNotContainStack)
        ->and($inventory->slots())->toBe($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects splitting the entire source stack', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItem(
        new ItemQuantity(50),
        new ItemStackingProfile(
            new ItemDefinitionId('iron-ore'),
            100,
            new StackCompatibilityKey('standard'),
        ),
    );

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(50),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSplitQuantity)
        ->and($inventory->slots())->toBe($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects splitting more than the source stack quantity', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $inventory->addItem(
        new ItemQuantity(50),
        new ItemStackingProfile(
            new ItemDefinitionId('iron-ore'),
            100,
            new StackCompatibilityKey('standard'),
        ),
    );

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(60),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSplitQuantity)
        ->and($inventory->slots())->toBe($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
});

it('rejects splitting into the same slot', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $result = $inventory->splitStack(
        0,
        0,
        new ItemQuantity(10),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SameSlot)
        ->and($inventory->version())->toBe(0);
});

it('rejects invalid source and destination indexes when splitting', function (int $sourceIndex, int $destinationIndex) {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        2,
    );

    $slotsBefore = $inventory->slots();
    $versionBefore = $inventory->version();

    $result = $inventory->splitStack(
        $sourceIndex,
        $destinationIndex,
        new ItemQuantity(10),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InvalidSlotIndex)
        ->and($inventory->slots())->toBe($slotsBefore)
        ->and($inventory->version())->toBe($versionBefore);
})->with([
    [-1, 1],
    [0, -1],
    [2, 1],
    [0, 2],
]);

it('records an event when inventory capacity increases', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        10,
    );

    $inventory->increaseCapacity(5);

    expect($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(InventoryCapacityIncreased::class)
        ->and($inventory->domainEvents()[0]->previousCapacity)->toBe(10)
        ->and($inventory->domainEvents()[0]->newCapacity)->toBe(15);
});

it('does not record an event when capacity increase fails', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        10,
    );

    expect(fn () => $inventory->increaseCapacity(0))
        ->toThrow(InvalidArgumentException::class);

    expect($inventory->domainEvents())->toBeEmpty();
});

it('clears recorded events when they are pulled', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        10,
    );

    $inventory->increaseCapacity(5);

    $events = $inventory->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InventoryCapacityIncreased::class)
        ->and($inventory->domainEvents())->toBeEmpty();
});

it('records an event when an item instance is added', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-1');

    $result = $inventory->addItemInstance($itemInstanceId);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])->toBeInstanceOf(ItemInstanceAddedToInventory::class)
        ->and($inventory->domainEvents()[0]->inventoryId)->toEqual($inventory->id())
        ->and($inventory->domainEvents()[0]->itemInstanceId)->toEqual($itemInstanceId)
        ->and($inventory->domainEvents()[0]->slotIndex)->toBe(0);
});

it('does not record an event when adding a duplicate item instance', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        3,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-1');

    $inventory->addItemInstance($itemInstanceId);

    $result = $inventory->addItemInstance($itemInstanceId);

    expect($result->isFailure())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1);
});

it('does not record an event when adding an item instance to a full inventory', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-1'),
        new CharacterId('character-1'),
        1,
    );

    $inventory->addItemInstance(new ItemInstanceId('item-instance-1'));

    $result = $inventory->addItemInstance(
        new ItemInstanceId('item-instance-2'),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1);
});

it('records an event when an item instance is removed', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 5,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-123');

    $inventory->addItemInstance($itemInstanceId);

    // Clear the addition event so we can inspect the removal event.
    $inventory->pullDomainEvents();

    $result = $inventory->removeItemInstance($itemInstanceId);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(ItemInstanceRemovedFromInventory::class);

    $event = $inventory->domainEvents()[0];

    expect($event->inventoryId->equals($inventory->id()))->toBeTrue()
        ->and($event->itemInstanceId->equals($itemInstanceId))->toBeTrue()
        ->and($event->slotIndex)->toBe(0);
});

it('does not record an event when an item instance cannot be removed', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 5,
    );

    $result = $inventory->removeItemInstance(
        new ItemInstanceId('missing-item-instance'),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::ItemInstanceNotFound)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(0);
});

it('records an event when items are added to the inventory', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $itemDefinitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $stackingProfile = new ItemStackingProfile(
        itemDefinitionId: $itemDefinitionId,
        maxStackSize: 100,
        compatibilityKey: $compatibilityKey,
    );

    $result = $inventory->addItem(
        quantity: new ItemQuantity(150),
        stackingProfile: $stackingProfile,
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1])
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(ItemsAddedToInventory::class);

    $event = $inventory->domainEvents()[0];

    expect($event->inventoryId->equals($inventory->id()))->toBeTrue()
        ->and($event->itemDefinitionId->equals($itemDefinitionId))->toBeTrue()
        ->and($event->compatibilityKey->equals($compatibilityKey))->toBeTrue()
        ->and($event->quantity)->toBe(150)
        ->and($event->slotIndexes)->toBe([0, 1])
        ->and($inventory->version())->toBe(1);
});

it('does not record an event when adding items exceeds inventory capacity', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 1,
    );

    $stackingProfile = new ItemStackingProfile(
        itemDefinitionId: new ItemDefinitionId('iron-ore'),
        maxStackSize: 100,
        compatibilityKey: new StackCompatibilityKey('standard'),
    );

    $result = $inventory->addItem(
        quantity: new ItemQuantity(150),
        stackingProfile: $stackingProfile,
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InsufficientCapacity)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(0)
        ->and($inventory->occupiedSlots())->toBe(0);
});

it('records an event when items are removed from the inventory', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $itemDefinitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $stackingProfile = new ItemStackingProfile(
        itemDefinitionId: $itemDefinitionId,
        maxStackSize: 100,
        compatibilityKey: $compatibilityKey,
    );

    $inventory->addItem(
        quantity: new ItemQuantity(150),
        stackingProfile: $stackingProfile,
    );

    $inventory->pullDomainEvents();

    $result = $inventory->removeItem(
        itemDefinitionId: $itemDefinitionId,
        compatibilityKey: $compatibilityKey,
        quantity: new ItemQuantity(120),
    );

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 1])
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(ItemsRemovedFromInventory::class);

    $event = $inventory->domainEvents()[0];

    expect($event->inventoryId->equals($inventory->id()))->toBeTrue()
        ->and($event->itemDefinitionId->equals($itemDefinitionId))->toBeTrue()
        ->and($event->compatibilityKey->equals($compatibilityKey))->toBeTrue()
        ->and($event->quantity)->toBe(120)
        ->and($event->slotIndexes)->toBe([0, 1])
        ->and($inventory->version())->toBe(2);
});

it('does not record an event when removing more items than the inventory contains', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $itemDefinitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $stackingProfile = new ItemStackingProfile(
        itemDefinitionId: $itemDefinitionId,
        maxStackSize: 100,
        compatibilityKey: $compatibilityKey,
    );

    $inventory->addItem(
        quantity: new ItemQuantity(50),
        stackingProfile: $stackingProfile,
    );

    $inventory->pullDomainEvents();

    $result = $inventory->removeItem(
        itemDefinitionId: $itemDefinitionId,
        compatibilityKey: $compatibilityKey,
        quantity: new ItemQuantity(100),
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::InsufficientQuantity)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(1)
        ->and($inventory->occupiedSlots())->toBe(1);
});

it('records an event when an item is moved within the inventory', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-123');

    $inventory->addItemInstance($itemInstanceId);
    $inventory->pullDomainEvents();

    $result = $inventory->moveToSlot(0, 2);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe([0, 2])
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(ItemMovedWithinInventory::class);

    $event = $inventory->domainEvents()[0];

    expect($event->inventoryId->equals($inventory->id()))->toBeTrue()
        ->and($event->sourceSlotIndex)->toBe(0)
        ->and($event->destinationSlotIndex)->toBe(2)
        ->and($inventory->version())->toBe(2);
});

it('does not record an event when moving an item fails', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $result = $inventory->moveToSlot(0, 2);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::SourceSlotEmpty)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(0);
});

it('records an event when inventory slots are swapped', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $firstItem = new ItemInstanceId('item-instance-1');
    $secondItem = new ItemInstanceId('item-instance-2');

    $inventory->addItemInstance($firstItem);
    $inventory->addItemInstance($secondItem);
    $inventory->pullDomainEvents();

    $result = $inventory->swapSlots(0, 1);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(InventorySlotsSwapped::class);

    $event = $inventory->domainEvents()[0];

    expect($event->inventoryId->equals($inventory->id()))->toBeTrue()
        ->and($event->firstSlotIndex)->toBe(0)
        ->and($event->secondSlotIndex)->toBe(1)
        ->and($inventory->slotAt(0)?->itemInstanceId()?->equals($secondItem))->toBeTrue()
        ->and($inventory->slotAt(1)?->itemInstanceId()?->equals($firstItem))->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('records an event when an occupied slot is swapped with an empty slot', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $itemInstanceId = new ItemInstanceId('item-instance-1');

    $inventory->addItemInstance($itemInstanceId);
    $inventory->pullDomainEvents();

    $result = $inventory->swapSlots(0, 2);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->domainEvents())->toHaveCount(1)
        ->and($inventory->domainEvents()[0])
        ->toBeInstanceOf(InventorySlotsSwapped::class);

    $event = $inventory->domainEvents()[0];

    expect($event->firstSlotIndex)->toBe(0)
        ->and($event->secondSlotIndex)->toBe(2)
        ->and($inventory->slotAt(0)?->isEmpty())->toBeTrue()
        ->and($inventory->slotAt(2)?->itemInstanceId()?->equals($itemInstanceId))->toBeTrue()
        ->and($inventory->version())->toBe(2);
});

it('does not record an event when swapping inventory slots fails', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $result = $inventory->swapSlots(0, 1);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::BothSlotsEmpty)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->version())->toBe(0);
});

it('merges the source stack into the destination stack', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(70), $profile);

    // Split 30 items from slot 0 into slot 1.
    $inventory->splitStack(0, 1, new ItemQuantity(30));

    $result = $inventory->mergeStacks(1, 0, $profile);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(70)
        ->and($inventory->slotAt(1)?->isEmpty())->toBeTrue()
        ->and($inventory->version())->toBe(3);
});

it('partially merges a source stack when the destination has limited capacity', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    // Creates stacks of 100 and 20.
    $inventory->addItem(new ItemQuantity(120), $profile);

    // Split 20 from slot 0 into the empty slot 2.
    // Slots now contain 80, 20, 20.
    $inventory->splitStack(0, 2, new ItemQuantity(20));

    // Combine slots 1 and 2, producing a stack of 40 in slot 2.
    $inventory->mergeStacks(1, 2, $profile);

    // Merge 40 into the destination stack of 80.
    // Only 20 can fit.
    $result = $inventory->mergeStacks(2, 0, $profile);

    expect($result->isSuccess())->toBeTrue()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(2)?->stack()?->quantity()->value())->toBe(20);
});

it('records an event when stacks are merged', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );
    // Create 70 items in slot 0.
    $inventory->addItem(new ItemQuantity(70), $profile);

    // Split 30 items into slot 1.
    $inventory->splitStack(0, 1, new ItemQuantity(30));

    // Clear events generated during setup.
    $inventory->pullDomainEvents();

    // Merge slot 1 into slot 0.
    $result = $inventory->mergeStacks(1, 0, $profile);

    $events = $inventory->pullDomainEvents();

    expect($result->isSuccess())->toBeTrue()
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ItemStacksMerged::class)
        ->and($events[0]->inventoryId)->toBe($inventory->id())
        ->and($events[0]->sourceSlotIndex)->toBe(1)
        ->and($events[0]->destinationSlotIndex)->toBe(0)
        ->and($events[0]->quantityMerged)->toBe(30);
});

it('records only the quantity actually merged in a partial merge event', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 3,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(70), $profile);

    $inventory->splitStack(0, 1, new ItemQuantity(30));

    $inventory->pullDomainEvents();

    $result = $inventory->mergeStacks(1, 0, $profile);

    $events = $inventory->pullDomainEvents();

    expect($result->isSuccess())->toBeTrue()
        ->and($events)->toHaveCount(1)
        ->and($events[0]->quantityMerged)->toBe(30);
});

it('does not record an event when the destination stack is full', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(
        new ItemQuantity(120),
        $profile,
    );

    // Slot 0 = 100, slot 1 = 20.
    $inventory->pullDomainEvents();

    $versionBefore = $inventory->version();

    $result = $inventory->mergeStacks(
        1,
        0,
        $profile,
    );

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryError::DestinationStackFull)
        ->and($inventory->version())->toBe($versionBefore)
        ->and($inventory->domainEvents())->toBeEmpty()
        ->and($inventory->slotAt(0)?->stack()?->quantity()->value())->toBe(100)
        ->and($inventory->slotAt(1)?->stack()?->quantity()->value())->toBe(20);
});

it('records an event when a stack is split', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(50), $profile);
    $inventory->pullDomainEvents();

    $result = $inventory->splitStack(
        0,
        1,
        new ItemQuantity(20),
    );

    $events = $inventory->pullDomainEvents();

    expect($result->isSuccess())->toBeTrue()
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ItemStackSplit::class)
        ->and($events[0]->inventoryId)->toBe($inventory->id())
        ->and($events[0]->sourceSlotIndex)->toBe(0)
        ->and($events[0]->destinationSlotIndex)->toBe(1)
        ->and($events[0]->quantitySplit)->toBe(20);
});

it('does not record an event when splitting a stack fails', function (): void {
    $inventory = new Inventory(
        id: new InventoryId('inventory-123'),
        characterId: new CharacterId('character-123'),
        capacity: 2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $inventory->addItem(new ItemQuantity(50), $profile);
    $inventory->pullDomainEvents();

    // Destination slot is occupied by the original stack.
    $result = $inventory->splitStack(
        0,
        0,
        new ItemQuantity(20),
    );

    expect($result->isFailure())
        ->toBeTrue()
        ->and($inventory->domainEvents())->toBeEmpty();
});
