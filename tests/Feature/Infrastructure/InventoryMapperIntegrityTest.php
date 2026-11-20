<?php

declare(strict_types=1);

use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryRecord;
use App\Modules\Inventory\Infrastructure\Persistence\Mappers\InventoryMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DB::table('inventories')->insert([
        'id' => 'inventory-corrupt',
        'character_id' => 'character-1',
        'capacity' => 1,
        'version' => 0,
    ]);
});

it('rejects an inventory slot containing both a stack and a unique item instance', function (): void {
    DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-corrupt',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'quantity' => 10,
        'compatibility_key' => 'standard',
        'item_instance_id' => 'item-instance-1',
    ]);

    $record = InventoryRecord::query()->findOrFail('inventory-corrupt');

    expect(
        fn () => app(InventoryMapper::class)->toDomain($record),
    )->toThrow(UnexpectedValueException::class);
});

it('rejects an inventory stack with a missing quantity', function (): void {
    DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-corrupt',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'compatibility_key' => 'standard',
    ]);

    $record = InventoryRecord::query()->findOrFail('inventory-corrupt');

    expect(
        fn () => app(InventoryMapper::class)->toDomain($record),
    )->toThrow(UnexpectedValueException::class);
});

it('rejects an inventory stack with a missing compatibility key', function (): void {
    DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-corrupt',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'quantity' => 10,
    ]);

    $record = InventoryRecord::query()->findOrFail('inventory-corrupt');

    expect(
        fn () => app(InventoryMapper::class)->toDomain($record),
    )->toThrow(UnexpectedValueException::class);
});

it('rejects incomplete stack data without an item definition', function (): void {
    DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-corrupt',
        'slot_index' => 0,
        'quantity' => 10,
        'compatibility_key' => 'standard',
    ]);

    $record = InventoryRecord::query()->findOrFail('inventory-corrupt');

    expect(
        fn () => app(InventoryMapper::class)->toDomain($record),
    )->toThrow(UnexpectedValueException::class);
});
