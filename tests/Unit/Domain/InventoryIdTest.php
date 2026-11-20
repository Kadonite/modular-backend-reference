<?php

use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use InvalidArgumentException;

it('stores an inventory identifier', function () {
    $id = new InventoryId('inventory-123');

    expect($id->value())->toBe('inventory-123');
});

it('considers identifiers with the same value equal', function () {
    $first = new InventoryId('inventory-123');
    $second = new InventoryId('inventory-123');

    expect($first->equals($second))->toBeTrue();
});

it('considers identifiers with different values unequal', function () {
    $first = new InventoryId('inventory-123');
    $second = new InventoryId('inventory-456');

    expect($first->equals($second))->toBeFalse();
});

it('rejects an empty identifier', function () {
    new InventoryId('');
})->throws(InvalidArgumentException::class);

it('rejects a whitespace-only identifier', function () {
    new InventoryId('   ');
})->throws(InvalidArgumentException::class);
