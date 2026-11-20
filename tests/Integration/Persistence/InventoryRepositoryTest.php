<?php

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Application\Enums\InventoryRepositoryError;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryRecord;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryRepository;
use App\Modules\Inventory\Infrastructure\Persistence\Mappers\InventoryMapper;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemStackingProfile;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('persists the current inventory state and all slots', function () {
    DB::table('inventories')->insert([
        'id' => 'inventory-123',
        'character_id' => 'character-123',
        'capacity' => 3,
        'version' => 0,
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
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 2,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
    ]);

    $repository = new EloquentInventoryRepository(new InventoryMapper);

    $inventory = $repository->findById(
        new InventoryId('inventory-123'),
    );

    expect($inventory)->not->toBeNull();

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $result = $inventory->addItem(new ItemQuantity(50), $profile);

    expect($result->isSuccess())->toBeTrue();

    $saveResult = $repository->save($inventory, 0);

    expect($saveResult->isSuccess())->toBeTrue()
        ->and($saveResult->value())->toBeNull();

    $record = InventoryRecord::query()->findOrFail('inventory-123');

    expect($record->version)->toBe(1)
        ->and($record->capacity)->toBe(3);

    $slots = DB::table('inventory_slots')
        ->where('inventory_id', 'inventory-123')
        ->orderBy('slot_index')
        ->get();

    expect($slots)->toHaveCount(3)
        ->and($slots[0]->item_definition_id)->toBe('iron-ore')
        ->and($slots[0]->quantity)->toBe(50)
        ->and($slots[0]->compatibility_key)->toBe('standard')
        ->and($slots[1]->item_definition_id)->toBeNull()
        ->and($slots[2]->item_definition_id)->toBeNull();
});

it('returns a concurrency conflict without changing persisted slots', function () {
    DB::table('inventories')->insert([
        'id' => 'inventory-123',
        'character_id' => 'character-123',
        'capacity' => 2,
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_slots')->insert([
        [
            'inventory_id' => 'inventory-123',
            'slot_index' => 0,
            'item_definition_id' => 'iron-ore',
            'quantity' => 50,
            'compatibility_key' => 'standard',
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

    $repository = new EloquentInventoryRepository(new InventoryMapper);

    $inventory = new Inventory(
        new InventoryId('inventory-123'),
        new CharacterId('character-123'),
        2,
    );

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('copper-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(25), $profile);

    $result = $repository->save($inventory, 0);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(InventoryRepositoryError::ConcurrencyConflict);

    $record = InventoryRecord::query()->findOrFail('inventory-123');

    expect($record->version)->toBe(1);

    $slots = DB::table('inventory_slots')
        ->where('inventory_id', 'inventory-123')
        ->orderBy('slot_index')
        ->get();

    expect($slots)->toHaveCount(2)
        ->and($slots[0]->item_definition_id)->toBe('iron-ore')
        ->and($slots[0]->quantity)->toBe(50)
        ->and($slots[1]->item_definition_id)->toBeNull();
});

it('rolls back inventory metadata and slot changes when slot persistence fails', function () {
    DB::table('inventories')->insert([
        'id' => 'inventory-rollback',
        'character_id' => 'character-123',
        'capacity' => 2,
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_slots')->insert([
        [
            'inventory_id' => 'inventory-rollback',
            'slot_index' => 0,
            'item_definition_id' => 'iron-ore',
            'quantity' => 50,
            'compatibility_key' => 'standard',
            'item_instance_id' => null,
        ],
        [
            'inventory_id' => 'inventory-rollback',
            'slot_index' => 1,
            'item_definition_id' => null,
            'quantity' => null,
            'compatibility_key' => null,
            'item_instance_id' => null,
        ],
    ]);

    DB::unprepared("
        CREATE OR REPLACE FUNCTION reject_inventory_slot_insert()
        RETURNS TRIGGER
        LANGUAGE plpgsql
        AS $$
        BEGIN
            RAISE EXCEPTION 'Simulated inventory slot persistence failure';
        END;
        $$
    ");

    DB::unprepared('
        CREATE TRIGGER reject_inventory_slot_insert_trigger
        BEFORE INSERT ON inventory_slots
        FOR EACH ROW
        EXECUTE FUNCTION reject_inventory_slot_insert()
    ');

    $repository = new EloquentInventoryRepository(new InventoryMapper);

    $inventory = $repository->findById(
        new InventoryId(
            'inventory-rollback',
        ),
    );

    expect($inventory)->not->toBeNull();

    $profile = new ItemStackingProfile(
        new ItemDefinitionId('copper-ore'),
        100,
        new StackCompatibilityKey('standard'),
    );

    $inventory->addItem(new ItemQuantity(25), $profile);

    $exceptionThrown = false;

    try {
        $repository->save($inventory, 1);
    } catch (QueryException) {
        $exceptionThrown = true;
    } finally {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS reject_inventory_slot_insert_trigger ON inventory_slots',
        );

        DB::unprepared(
            'DROP FUNCTION IF EXISTS reject_inventory_slot_insert()',
        );
    }

    expect($exceptionThrown)->toBeTrue();

    $record = InventoryRecord::query()
        ->findOrFail('inventory-rollback');

    expect($record->version)->toBe(1);

    $slots = DB::table('inventory_slots')
        ->where('inventory_id', 'inventory-rollback')
        ->orderBy('slot_index')
        ->get();

    expect($slots)->toHaveCount(2)
        ->and($slots[0]->item_definition_id)->toBe('iron-ore')
        ->and($slots[0]->quantity)->toBe(50)
        ->and($slots[1]->item_definition_id)->toBeNull();
});
