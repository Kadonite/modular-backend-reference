
<?php

use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryRecord;
use App\Modules\Inventory\Infrastructure\Persistence\Mappers\InventoryMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('rehydrates an inventory aggregate with mixed slot contents', function () {
    DB::table('inventories')->insert([
        'id' => 'inventory-123',
        'character_id' => 'character-123',
        'capacity' => 4,
        'version' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_slots')->insert([
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
            'item_definition_id' => 'iron-ore',
            'quantity' => 50,
            'compatibility_key' => 'standard',
            'item_instance_id' => null,
        ],
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 2,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => 'instance-123',
        ],
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 3,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
    ]);

    $record = InventoryRecord::query()
        ->findOrFail('inventory-123');

    $inventory = (new InventoryMapper)->toDomain($record);

    expect($inventory->id()->value())->toBe('inventory-123')
        ->and($inventory->characterId()->value())->toBe('character-123')
        ->and($inventory->capacity())->toBe(4)
        ->and($inventory->version())->toBe(3)
        ->and($inventory->slots())->toHaveCount(4);

    expect($inventory->slotAt(0)->isEmpty())->toBeTrue();

    $stack = $inventory->slotAt(1)->stack();

    expect($stack)->not->toBeNull()
        ->and($stack->itemDefinitionId()->value())->toBe('iron-ore')
        ->and($stack->quantity()->value())->toBe(50)
        ->and($stack->compatibilityKey()->value())->toBe('standard');

    expect($inventory->slotAt(2)->itemInstanceId()->value())
        ->toBe('instance-123');

    expect($inventory->slotAt(3)->isEmpty())->toBeTrue();
});
