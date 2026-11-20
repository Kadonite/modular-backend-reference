<?php

declare(strict_types=1);

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Infrastructure\Persistence\Mappers\InventoryMapper;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\ItemStackingProfile;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;

it('maps inventory metadata to persistence attributes', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        5,
    );

    $inventory->addItemInstance(new ItemInstanceId('instance-123'));

    $mapper = new InventoryMapper;

    expect($mapper->toRecordAttributes($inventory))->toBe([
        'id' => 'inventory-123',
        'character_id' => 'character-123',
        'capacity' => 5,
        'version' => 1,
    ]);
});

it('maps empty inventory slots to persistence attributes', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        2,
    );

    $mapper = new InventoryMapper;

    expect($mapper->toSlotRecordAttributes($inventory))->toBe([
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 0,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 1,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
    ]);
});

it('maps item stacks to persistence attributes', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(50), $profile);

    $mapper = new InventoryMapper;

    $records = $mapper->toSlotRecordAttributes($inventory);

    expect($records[0])->toBe([
        'inventory_id' => 'inventory-123',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'quantity' => 50,
        'compatibility_key' => 'standard',
        'item_instance_id' => null,
    ]);
});

it('maps unique item instances to persistence attributes', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        2,
    );

    $inventory->addItemInstance(new ItemInstanceId('instance-123'));

    $mapper = new InventoryMapper;

    $records = $mapper->toSlotRecordAttributes($inventory);

    expect($records[0])->toBe([
        'inventory_id' => 'inventory-123',
        'slot_index' => 0,
        'item_definition_id' => null,
        'quantity' => null,
        'compatibility_key' => null,
        'item_instance_id' => 'instance-123',
    ]);
});

it('preserves slot indexes and content when mapping mixed inventory contents', function (): void {
    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        3,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(50), $profile);
    $inventory->addItemInstance(new ItemInstanceId('instance-123'));

    $mapper = new InventoryMapper;

    $records = $mapper->toSlotRecordAttributes($inventory);

    expect($records)->toHaveCount(3)
        ->and($records[0]['slot_index'])->toBe(0)
        ->and($records[0]['quantity'])->toBe(50)
        ->and($records[1]['slot_index'])->toBe(1)
        ->and($records[1]['item_instance_id'])->toBe('instance-123')
        ->and($records[2]['slot_index'])->toBe(2)
        ->and($records[2]['item_definition_id'])->toBeNull();
});
