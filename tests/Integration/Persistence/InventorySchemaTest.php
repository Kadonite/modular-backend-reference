
<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DB::table('inventories')->insert([
        'id' => 'inventory-constraints',
        'character_id' => 'character-123',
        'capacity' => 5,
        'version' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('rejects an inventory with zero capacity', function (): void {
    expect(fn () => DB::table('inventories')->insert([
        'id' => 'inventory-zero-capacity',
        'character_id' => 'character-123',
        'capacity' => 0,
        'version' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects an inventory with negative capacity', function (): void {
    expect(fn () => DB::table('inventories')->insert([
        'id' => 'inventory-negative-capacity',
        'character_id' => 'character-123',
        'capacity' => -1,
        'version' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects an inventory with a negative version', function (): void {
    expect(fn () => DB::table('inventories')->insert([
        'id' => 'inventory-negative-version',
        'character_id' => 'character-123',
        'capacity' => 5,
        'version' => -1,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects a slot with a negative index', function (): void {
    expect(fn () => DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-constraints',
        'slot_index' => -1,
        'item_definition_id' => null,
        'quantity' => null,
        'compatibility_key' => null,
        'item_instance_id' => null,
    ]))->toThrow(QueryException::class);
});

it('rejects a stack slot with a null quantity', function (): void {
    expect(fn () => DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-constraints',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'quantity' => null,
        'compatibility_key' => 'standard',
        'item_instance_id' => null,
    ]))->toThrow(QueryException::class);
});

it('rejects a slot containing both a stack and a unique item instance', function (): void {
    expect(fn () => DB::table('inventory_slots')->insert([
        'inventory_id' => 'inventory-constraints',
        'slot_index' => 0,
        'item_definition_id' => 'iron-ore',
        'quantity' => 10,
        'compatibility_key' => 'standard',
        'item_instance_id' => 'item-instance-123',
    ]))->toThrow(QueryException::class);
});
