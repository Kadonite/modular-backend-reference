<?php

use App\Modules\Inventory\Domain\Entities\InventorySlot;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use InvalidArgumentException;

it('creates an empty inventory slot', function () {
    $slot = new InventorySlot(0);

    expect($slot->index())->toBe(0)
        ->and($slot->isEmpty())->toBeTrue()
        ->and($slot->content())->toBeNull();
});

it('stores an item stack', function () {
    $stack = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    $slot = new InventorySlot(2, $stack);

    expect($slot->index())->toBe(2)
        ->and($slot->isEmpty())->toBeFalse()
        ->and($slot->containsStack())->toBeTrue()
        ->and($slot->stack())->toBe($stack)
        ->and($slot->itemInstanceId())->toBeNull();
});

it('stores an individual item instance', function () {
    $itemInstanceId = new ItemInstanceId('instance-123');

    $slot = new InventorySlot(3, $itemInstanceId);

    expect($slot->index())->toBe(3)
        ->and($slot->isEmpty())->toBeFalse()
        ->and($slot->containsItemInstance())->toBeTrue()
        ->and($slot->itemInstanceId())->toBe($itemInstanceId)
        ->and($slot->stack())->toBeNull();
});

it('rejects a negative slot index', function () {
    new InventorySlot(-1);
})->throws(InvalidArgumentException::class);
